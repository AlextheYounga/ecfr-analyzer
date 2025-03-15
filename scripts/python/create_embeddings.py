import os
import psycopg2
import datetime
from sentence_transformers import SentenceTransformer
from transformers import AutoTokenizer
from dotenv import load_dotenv
from tqdm import tqdm
import nltk


class EmbeddingGenerator:
	def __init__(self):
		print("Initializing models...")
		load_dotenv()
		self.chunk_size = 1024	
		self.tokenizer = AutoTokenizer.from_pretrained("baseten/Meta-Llama-3-tokenizer")
		self.embedding_model = SentenceTransformer("mixedbread-ai/mxbai-embed-large-v1")
		self.conn, self.db = self.__load_db()


	def fetch_sections(self):
		# Get all sections from db
		print('Pulling sections content from DB...')
		self.db.execute("""
		SELECT tc.id, tc.title_id, tc.content, t.name, t.latest_issue_date, te.label
		FROM title_contents tc
		JOIN titles t ON tc.title_id = t.id
		JOIN title_entities te ON tc.title_entity_id = te.id;
		""")
		rows = self.db.fetchall()
		return rows

	def get_last_inserted_content_id(self):
		# Get all sections from db
		self.db.execute("""
		SELECT content_id FROM cfr_chunks ORDER BY id DESC LIMIT 1;
		""")
		row = self.db.fetchone()
		if not row: return 0
		return row[0]

	def chunk_large_text(self, text):
		chunks = []
		current_chunk = ""
		sentences = nltk.tokenize.sent_tokenize(text, language='english')
		for sentence in sentences:
			current_chunk += sentence
			tokens = self.tokenizer.encode(current_chunk)
			token_count = len(tokens)
			if token_count > self.chunk_size:
				chunks.append(current_chunk)
				current_chunk = ""
		if current_chunk:
			chunks.append(current_chunk)
		return chunks
		

	def create_embeddings(self, text):
		tokens = self.tokenizer.encode(text)
		token_count = len(tokens)
		embeddings = []
		if token_count > self.chunk_size:
			chunks = self.chunk_large_text(text)
			for chunk in chunks:
				embeddings.append(self.embedding_model.encode(chunk).tolist())
		else:
			embeddings = [self.embedding_model.encode(text).tolist()]
		return embeddings
	

	def save_embeddings(self, row, embeddings):
		content_id: int = row[0]
		title_id: int = row[1]
		title_name: str = row[3]
		issue_date: datetime = row[4]
		section_name: str = row[5]

		# Insert into database
		for chunk, embedding in enumerate(embeddings):
			self.db.execute(
				"INSERT INTO cfr_chunks (title_id, content_id, title_name, section_name, issue_date, chunk_index, embedding) VALUES (%s, %s, %s, %s, %s, %s, %s)",
				(title_id, content_id, title_name, section_name, issue_date, chunk, embedding)
			)
		self.conn.commit()


	def close(self):
		self.db.close()
		self.conn.close()


	def __load_db(self):
		# Connect to PostgreSQL
		conn = psycopg2.connect(
			dbname=os.getenv("DB_DATABASE"),
			user=os.getenv("DB_USERNAME"),
			password=os.getenv("DB_PASSWORD"),
			host=os.getenv("DB_HOST"),
			port=os.getenv("DB_PORT")
		)
		cur = conn.cursor()
		return conn, cur
	

if __name__ == "__main__":
	generator = EmbeddingGenerator()
	rows = generator.fetch_sections()
	last_id = generator.get_last_inserted_content_id()

	for row in tqdm(rows):
		id = row[0]
		if id <= last_id:
			continue

		text = row[2]
		embeddings = generator.create_embeddings(text)	
		generator.save_embeddings(row, embeddings)

	generator.close()