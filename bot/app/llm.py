"""Провайдер-агностичный LLM через OpenAI-совместимый API (Grok / Hermes / любой)."""
from langchain_openai import ChatOpenAI
from .config import LLM_BASE_URL, LLM_MODEL, LLM_API_KEY, LLM_TEMPERATURE, LLM_CONFIGURED


def build_llm():
    if not LLM_CONFIGURED:
        return None
    return ChatOpenAI(
        model=LLM_MODEL,
        base_url=LLM_BASE_URL,
        api_key=LLM_API_KEY,
        temperature=LLM_TEMPERATURE,
    )
