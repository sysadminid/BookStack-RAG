# cli.py
import typer
from src.ingest import full_ingest, incremental_ingest

app = typer.Typer(help="BookStack RAG CLI")

@app.command()
def ingest_full():
    """Full reindex."""
    res = full_ingest()
    typer.echo(res)

@app.command()
def ingest_incremental(updated_after: str):
    """
    Incremental reindex, e.g. '2025-09-01T00:00:00'
    """
    res = incremental_ingest(updated_after)
    typer.echo(res)

if __name__ == "__main__":
    app()
