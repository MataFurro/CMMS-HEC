import zipfile
import xml.etree.ElementTree as ET

filename = r'c:\Users\star_\OneDrive\Escritorio\Prueba 2.xlsx'

try:
    with zipfile.ZipFile(filename, 'r') as z:
        shared_strings = []
        if 'xl/sharedStrings.xml' in z.namelist():
            with z.open('xl/sharedStrings.xml') as f:
                tree = ET.parse(f)
                root = tree.getroot()
                # The namespace is usually required
                ns = {'x': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
                for si in root.findall('x:si', ns):
                    t = si.find('x:t', ns)
                    val = t.text if t is not None else ""
                    shared_strings.append(val)

        if 'xl/worksheets/sheet1.xml' in z.namelist():
            with z.open('xl/worksheets/sheet1.xml') as f:
                tree = ET.parse(f)
                root = tree.getroot()
                ns = {'x': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
                sheetData = root.find('x:sheetData', ns)
                if sheetData is not None:
                    first_row = sheetData.find('x:row', ns)
                    if first_row is not None:
                        headers = []
                        for c in first_row.findall('x:c', ns):
                            v = c.find('x:v', ns)
                            val = v.text if v is not None else ""
                            if c.get('t') == 's' and val.isdigit():
                                val = shared_strings[int(val)]
                            headers.append(val)
                        print("HEADERS:")
                        for i, h in enumerate(headers):
                            print(f"{i+1}: {h}")
except Exception as e:
    print("Error:", e)
