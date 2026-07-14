"""ИИ-оператор на LangGraph: assistant ↔ tools, с памятью по диалогу.
LLM провайдер-агностичен (Grok/Hermes). Граф собран вручную — стабилен между версиями."""
from langgraph.graph import StateGraph, START, END, MessagesState
from langgraph.prebuilt import ToolNode, tools_condition
from langgraph.checkpoint.memory import MemorySaver
from langchain_core.messages import SystemMessage, HumanMessage

from .llm import build_llm
from .tools import ALL_TOOLS
from .prompts import SYSTEM_PROMPT

_llm = build_llm()
_graph = None

if _llm is not None:
    _llm_tools = _llm.bind_tools(ALL_TOOLS)

    async def assistant(state: MessagesState):
        msgs = [SystemMessage(content=SYSTEM_PROMPT)] + state["messages"]
        return {"messages": [await _llm_tools.ainvoke(msgs)]}

    builder = StateGraph(MessagesState)
    builder.add_node("assistant", assistant)
    builder.add_node("tools", ToolNode(ALL_TOOLS))
    builder.add_edge(START, "assistant")
    builder.add_conditional_edges("assistant", tools_condition)
    builder.add_edge("tools", "assistant")
    _graph = builder.compile(checkpointer=MemorySaver())


def is_ready() -> bool:
    return _graph is not None


async def reply(thread_id: str, text: str) -> str:
    """Обрабатывает сообщение клиента в контексте диалога thread_id."""
    if _graph is None:
        return ("ИИ-оператор ещё не подключён (не задан LLM_API_KEY). "
                "Обратитесь к менеджеру: +7 (343) 454-77-88.")
    out = await _graph.ainvoke(
        {"messages": [HumanMessage(content=text)]},
        config={"configurable": {"thread_id": thread_id}},
    )
    return out["messages"][-1].content or "…"
