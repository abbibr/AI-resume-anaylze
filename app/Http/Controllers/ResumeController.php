<?php

namespace App\Http\Controllers;

use App\Models\ResumeAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;
use LanguageDetection\Language;

class ResumeController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'resume' => 'required|mimes:pdf|max:2048',
        ]);

        $pdf = $request->file('resume');
        $filename = time() . '_' . $pdf->getClientOriginalName();
        $path = public_path('resumes/' . $filename);
        $pdf->move(public_path('resumes'), $filename);

        // Call Python script
        $text = shell_exec("python ../resume-parser/main.py " . escapeshellarg($path));

        // Clean text to valid UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Optionally remove invalid UTF-8 characters
        $text = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $text);

        if (!$text) {
            return back()->with('error', 'Failed to extract text from the resume. Please check your Python script.');
        }

        // ----------- LANGUAGE DETECTION HERE ----------------
        $ld = new Language;
        $language = $ld->detect($text)->bestResults()->close();
        // $language is array like ['en' => 0.8] — get the top key:
        $langCode = key($language);
        $langShort = substr($langCode, 0, 2);

        // Build AI prompt based on detected language
        switch ($langShort) {
            case 'uz':
            case 'ug': // Uyghur and Uzbek both use Latin, so group them if you want
                $systemPrompt = 'Siz professional HR mutaxassisiz. Iltimos, rezyumeni tahlil qiling va o\'zbek tilida javob bering.';
                $userPrompt = "Iltimos, ushbu rezyumeni tahlil qiling va kuchli tomonlari, zaif tomonlari va yaxshilash bo'yicha tavsiyalarni bering:\n\n" . $text;
                break;
            case 'ru':
                $systemPrompt = 'Вы профессиональный HR эксперт. Пожалуйста, анализируйте резюме и отвечайте на русском языке.';
                $userPrompt = "Проанализируйте это резюме и дайте сильные стороны, слабые стороны и рекомендации для улучшения:\n\n" . $text;
                break;
            default:
                $systemPrompt = 'You are a professional HR expert analyzing a resume.';
                $userPrompt = "Analyze this resume and give strengths, weaknesses, and improvement tips:\n\n" . $text;
        }

        // ----------------------------------------------------

        // Right after detecting language:
        Log::info('Detected language:', $language);

        // Send to OpenAI
        $client = OpenAI::client(env('OPENAI_API_KEY'));
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $analysis = $response['choices'][0]['message']['content'];

        // Save to DB
        ResumeAnalysis::create([
            'user_id' => Auth::id(),
            'filename' => $filename,
            'extracted_text' => $text ? $text : 'none',
            'analysis' => $analysis,
        ]);

        return view('upload', ['analysis' => $analysis]);
    }

    public function history()
    {
        $analyses = ResumeAnalysis::where('user_id', Auth::id())->latest()->get();
        return view('history', compact('analyses'));
    }
}
