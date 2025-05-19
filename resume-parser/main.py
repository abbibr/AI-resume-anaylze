# resume-parser/main.py
import sys
import PyPDF2

def extract_text_from_pdf(path):
    with open(path, 'rb') as file:
        reader = PyPDF2.PdfReader(file)
        text = ''
        for page in reader.pages:
            text += page.extract_text() + '\n'
        return text

if __name__ == "__main__":
    pdf_path = sys.argv[1]
    print(extract_text_from_pdf(pdf_path))
