from zipfile import ZipFile

from lxml import etree


path = r"artifacts\Prohibiciones_y_Consecuencias_Usuarios_Externos_INSO.docx"
ns = {
    "w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
}

with ZipFile(path) as archive:
    document = etree.fromstring(archive.read("word/document.xml"))
    numbering = etree.fromstring(archive.read("word/numbering.xml"))
    relationships = etree.fromstring(archive.read("word/_rels/document.xml.rels"))

tables = document.xpath("//w:tbl", namespaces=ns)
paragraphs = document.xpath("//w:body/w:p", namespaces=ns)
numbered = document.xpath("//w:p[w:pPr/w:numPr]", namespaces=ns)
links = relationships.xpath(
    '//*[local-name()="Relationship" and contains(@Type,"hyperlink")]'
)
section = document.xpath("//w:sectPr", namespaces=ns)[0]

assert len(tables) == 1, f"Expected one data table, found {len(tables)}"
table = tables[0]
assert table.xpath("string(w:tblPr/w:tblW/@w:w)", namespaces=ns) == "9360"
assert table.xpath("string(w:tblPr/w:tblInd/@w:w)", namespaces=ns) == "120"
assert sum(map(int, table.xpath("w:tblGrid/w:gridCol/@w:w", namespaces=ns))) == 9360
assert len(document.xpath("//w:tr[w:trPr/w:tblHeader]", namespaces=ns)) == 1
assert len(numbered) == 20
assert len(links) == 4
assert section.xpath("string(w:pgSz/@w:w)", namespaces=ns) == "12240"
assert section.xpath("string(w:pgSz/@w:h)", namespaces=ns) == "15840"
for margin in ("left", "right", "top", "bottom"):
    assert section.xpath(f"string(w:pgMar/@w:{margin})", namespaces=ns) == "1440"

print(f"paragraphs={len(paragraphs)}")
print(f"tables={len(tables)}")
print(f"numbered_paragraphs={len(numbered)}")
print(f"hyperlinks={len(links)}")
print(f"abstract_numbering={len(numbering.xpath('//w:abstractNum', namespaces=ns))}")
print("structural_audit=PASS")
