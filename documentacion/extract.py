import xml.etree.ElementTree as ET

tree = ET.parse('c:\\laragon\\www\\cnsb_lab\\documentacion\\mwb_extracted\\document.mwb.xml')
root = tree.getroot()

tables = []
for obj in root.findall('.//value[@struct-name="db.mysql.Table"]'):
    table_name = ""
    for attr in obj.findall('value'):
        if attr.get('key') == 'name':
            table_name = attr.text
            break
    
    if not table_name: continue
    
    columns = []
    for col_obj in obj.findall('.//value[@struct-name="db.mysql.Column"]'):
        col_name = ""
        for attr in col_obj.findall('value'):
            if attr.get('key') == 'name':
                col_name = attr.text
                break
        if col_name:
            columns.append(col_name)
    
    tables.append(f"Table: {table_name}\nColumns: {', '.join(columns)}")

with open('c:\\laragon\\www\\cnsb_lab\\documentacion\\schema_dump.txt', 'w') as f:
    f.write('\n\n'.join(tables))
print('Schema extracted successfully.')
