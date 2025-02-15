use serde_json::Value;


// We will create a list of sections here each containing a parents array.
pub fn flatten_structure(structure: Value) -> Vec<Value> {
    // This will hold the flattened structures
    let mut sections = Vec::new();

    // Recursively flatten the JSON structure
    walk_structure_sections(&structure, &Vec::new(), &mut sections);

    return sections;
}

/* 
*  	Recursively walk `structure`. If there's a "children" field:
*  		- Remove it from the current node (so we don't duplicate it later).
*  		- Recursively flatten each child, accumulating parents along the way.
* 	Otherwise, if there's no "children" field:
*  		- It's a leaf node, so we insert the accumulated `parents` and push it into the `sections` vector.	
*/ 
fn walk_structure_sections(structure: &Value, parents: &Vec<Value>, sections: &mut Vec<Value>) {
    if let Some(children) = structure.get("children") {
        // If `children` is present and is an array
        if let Some(children_array) = children.as_array() {
            // Clone the current object so we can strip out the "children"
            if let Some(obj) = structure.as_object() {
                let mut parent_obj = obj.clone();
                parent_obj.remove("children");

                // Convert our "parent_obj" back to a Value
                let parent_value = Value::Object(parent_obj);

                // Append that parent to the list of parents
                let mut new_parents = parents.clone();
                new_parents.push(parent_value);

                // Recurse for each child
                for child in children_array {
                    walk_structure_sections(child, &new_parents, sections);
                }
            }
        }
    } else {
        // No "children" => This is a leaf node
        if let Some(obj) = structure.as_object() {
            let mut leaf_obj = obj.clone();
            // Insert the "parents" array
            leaf_obj.insert("parents".to_string(), Value::Array(parents.clone()));
            sections.push(Value::Object(leaf_obj));
        }
    }
}
