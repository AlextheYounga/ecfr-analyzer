import os
from llama_cpp import Llama
import numpy as np
import psycopg2
from dotenv import load_dotenv

load_dotenv()

# Connect to PostgreSQL
conn = psycopg2.connect(
	dbname=os.getenv("DB_DATABASE"),
	user=os.getenv("DB_USERNAME"),
	password=os.getenv("DB_PASSWORD"),
	host=os.getenv("DB_HOST"),
	port=os.getenv("DB_PORT")
)
cur = conn.cursor()

# Refresh table
cur.execute("TRUNCATE TABLE cfr_chunks;")

# Path to your GGUF model file (change this to your actual model path)
MODEL_PATH = os.getenv("LLAMA_MODEL")	

# Load the model with embedding support
llm = Llama(model_path=MODEL_PATH, n_ctx=2048, embedding=True)

# Get all sections from db
cur.execute("""
SELECT tc.*, t.name, t.latest_issue_date, te.label
FROM title_contents tc
JOIN titles t ON tc.title_id = t.id
JOIN title_entities te ON tc.title_entity_id = te.id;
""")
rows = cur.fetchall()

for row in rows:
	# Example eCFR text chunk
	content_id=row[0]
	title_id=row[1]
	title_entity_id=row[2]	
	cfr_text = row[3]
	words = row[4]
	title_name = row[5]
	issue_date = row[6]
	section_name = row[7]

	# Generate embeddings
	response = llm.create_embedding(input=cfr_text)

	# Extract the embedding vector
	embedding = response["data"][0]["embedding"]

	# Insert into database
	cur.execute(
	    "INSERT INTO cfr_chunks (title_id, content_id, title_name, section_name, issue_date, embedding) VALUES (%s, %s, %s, %s, %s)",
	    (title_id, content_id, title_name, section_name, issue_date, embedding)
	)

conn.commit()
cur.close()
conn.close()