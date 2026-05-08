"""One-off: rasterize first page of GWUSA/GMUSA PDFs to transparent PNGs for Dompdf."""
import sys
from pathlib import Path

try:
    import fitz  # PyMuPDF
except ImportError:
    print("pip install pymupdf", file=sys.stderr)
    sys.exit(1)


def pdf_to_png(src: Path, dest: Path, zoom: float = 3.0) -> None:
    doc = fitz.open(src)
    page = doc[0]
    mat = fitz.Matrix(zoom, zoom)
    pix = page.get_pixmap(matrix=mat, alpha=True)
    dest.parent.mkdir(parents=True, exist_ok=True)
    pix.save(dest.as_posix())
    doc.close()


if __name__ == "__main__":
    root = Path(__file__).resolve().parent.parent / "assets" / "img"
    pdf_to_png(root / "gwusa-logo.pdf", root / "logo-grant-writing-usa.png")
    pdf_to_png(root / "gmusa-logo.pdf", root / "logo-grant-management-usa.png")
    print("Wrote:", root / "logo-grant-writing-usa.png")
    print("Wrote:", root / "logo-grant-management-usa.png")
