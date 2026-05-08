"""
Generate transparent-background SVG seals for GWUSA and GMUSA,
and strip white/near-white backgrounds from the logo PNGs.
"""
import math
import os
from PIL import Image

PLUGIN_IMG = os.path.join(
    os.path.dirname(__file__), '..', 'assets', 'img'
)


# ---------------------------------------------------------------------------
# SVG seal generator
# ---------------------------------------------------------------------------

def generate_seal_svg(acronym: str, full_name: str) -> str:
    CX, CY = 100, 100
    GOLD     = "#b8952a"
    DK_GOLD  = "#7a5c10"

    # Two staggered rings of ellipses for the wreath
    # Leaves are tangentially oriented (rx=radial, ry=tangential after rotation)
    outer_r, inner_r = 77, 70
    n = 26
    outer_leaves, inner_leaves = [], []

    for i in range(n):
        a  = (360 / n) * i
        ar = math.radians(a)
        lx = CX + outer_r * math.cos(ar)
        ly = CY + outer_r * math.sin(ar)
        rot = a + 90  # tangent direction
        outer_leaves.append(
            f'<ellipse cx="{lx:.2f}" cy="{ly:.2f}" rx="2.7" ry="7.2" '
            f'fill="{GOLD}" transform="rotate({rot:.1f},{lx:.2f},{ly:.2f})"/>'
        )

    for i in range(n):
        a  = (360 / n) * i + (180 / n)  # offset half a step
        ar = math.radians(a)
        lx = CX + inner_r * math.cos(ar)
        ly = CY + inner_r * math.sin(ar)
        rot = a + 90
        inner_leaves.append(
            f'<ellipse cx="{lx:.2f}" cy="{ly:.2f}" rx="2.0" ry="5.2" '
            f'fill="{DK_GOLD}" transform="rotate({rot:.1f},{lx:.2f},{ly:.2f})"/>'
        )

    leaf_svg = '\n  '.join(outer_leaves + inner_leaves)

    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">

  <!-- Outer beaded ring -->
  <circle cx="{CX}" cy="{CY}" r="97" fill="none" stroke="{GOLD}"
          stroke-width="2" stroke-dasharray="2.4,1.8"/>
  <!-- Outer solid ring -->
  <circle cx="{CX}" cy="{CY}" r="91" fill="none" stroke="{GOLD}" stroke-width="2"/>
  <!-- Inner ring borders -->
  <circle cx="{CX}" cy="{CY}" r="63" fill="none" stroke="{GOLD}" stroke-width="1.5"/>
  <circle cx="{CX}" cy="{CY}" r="57" fill="none" stroke="{GOLD}" stroke-width="0.75"/>

  <!-- Laurel wreath leaves -->
  {leaf_svg}

  <!-- Acronym -->
  <text x="{CX}" y="97" fill="{GOLD}"
        font-family="Times New Roman,Georgia,serif"
        font-size="24" font-weight="700" text-anchor="middle"
        letter-spacing="1">{acronym}</text>

  <!-- Full name -->
  <text x="{CX}" y="114" fill="{GOLD}"
        font-family="Times New Roman,Georgia,serif"
        font-size="7" text-anchor="middle"
        letter-spacing="2">{full_name}</text>

  <!-- Decorative dots -->
  <circle cx="{CX - 9}" cy="125" r="1.5" fill="{GOLD}"/>
  <circle cx="{CX}"     cy="125" r="1.5" fill="{GOLD}"/>
  <circle cx="{CX + 9}" cy="125" r="1.5" fill="{GOLD}"/>

</svg>
'''


# ---------------------------------------------------------------------------
# Logo PNG – remove near-white background
# ---------------------------------------------------------------------------

def strip_white_bg(src_path: str, threshold: int = 235) -> None:
    img  = Image.open(src_path).convert("RGBA")
    data = list(img.getdata())
    out  = []
    for r, g, b, a in data:
        if r >= threshold and g >= threshold and b >= threshold:
            out.append((r, g, b, 0))
        else:
            out.append((r, g, b, a))
    img.putdata(out)
    img.save(src_path)
    print(f"  Stripped white bg: {os.path.basename(src_path)}")


# ---------------------------------------------------------------------------
# Run
# ---------------------------------------------------------------------------

os.makedirs(PLUGIN_IMG, exist_ok=True)

seals = [
    ("seal-grant-writing-usa.svg",    "GWUSA", "GRANT WRITING USA"),
    ("seal-grant-management-usa.svg", "GMUSA", "GRANT MANAGEMENT USA"),
]

for fname, acronym, full_name in seals:
    out = os.path.join(PLUGIN_IMG, fname)
    with open(out, "w", encoding="utf-8") as fh:
        fh.write(generate_seal_svg(acronym, full_name))
    print(f"  Generated SVG seal: {fname}")

logos = [
    "logo-grant-writing-usa.png",
    "logo-grant-management-usa.png",
]

for fname in logos:
    src = os.path.join(PLUGIN_IMG, fname)
    if os.path.isfile(src):
        strip_white_bg(src)
    else:
        print(f"  Logo not found (skip): {fname}")

print("Done.")
