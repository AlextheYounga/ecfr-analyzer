import json

structure = json.loads(open('storage/app/private/title-1-2015-12-18.json').read())

sections = []
# Get a list of all sections (lowest level of structure) with an array of their parents
def flatten_structure(structure, parents=[]):
	if 'children' in structure:
		for child in structure['children']:
			parent = { **structure }
			del parent['children']
			flatten_structure(child, parents + [parent])
	else:
		sections.append({
			**structure,
			'parents': parents
		})

flatten_structure(structure)

with open('storage/app/private/title-1-2015-12-18-flattened.json', 'w') as f:
	f.write(json.dumps(sections, indent=4))