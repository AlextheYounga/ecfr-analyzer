import json
import os
import sys

def decode_json_string(data):
    """Tries to decode a JSON string multiple times if it's double-encoded."""
    try:
        while isinstance(data, str):
            data = json.loads(data)  # Attempt to parse JSON
    except (json.JSONDecodeError, TypeError):
        pass  # If decoding fails, return the last successfully decoded data
    return data

def clean_json_file(file_path):
    """Clean a JSON file by decoding improperly escaped JSON and re-encoding it in a minified format."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            raw_data = f.read()

        # Decode any over-encoded JSON
        json_data = decode_json_string(raw_data)

        # Minify JSON and remove unnecessary escape sequences
        cleaned_json = json.dumps(json_data, separators=(',', ':'))  # Minified output

        # Write back the cleaned, minified JSON
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(cleaned_json)

        print(f"Cleaned and minified JSON file: {file_path}")

    except json.JSONDecodeError as e:
        print(f"Error decoding JSON in {file_path}: {e}")
    except Exception as e:
        print(f"Error processing {file_path}: {e}")

def clean_json_files_in_directory(directory):
    """Walk through the directory and clean all JSON files."""
    for root, _, files in os.walk(directory):
        for file in files:
            if file.endswith(".json"):
                file_path = os.path.join(root, file)
                clean_json_file(file_path)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python clean_json.py <directory_path>")
        sys.exit(1)

    directory_path = sys.argv[1]
    clean_json_files_in_directory(directory_path)
