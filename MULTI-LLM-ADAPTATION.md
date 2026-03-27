# Multi-LLM Adaptation Guide

> Hướng dẫn chuyển đổi Agentic Software Team sang các LLM platform khác

---

## 📋 Tổng quan

Hệ thống hiện tại được tối ưu cho **Claude Code**, nhưng kiến trúc dựa trên:
- **Markdown files** (agent definitions, commands)
- **Git workflow** (branch strategy, commits)
- **File system structure** (workspace, templates)

→ **95% logic có thể tái sử dụng** cho các LLM khác.

---

## 🔄 Port sang ChatGPT

### Option 1: Custom GPT với Knowledge Base

```yaml
Name: "Agentic BA"
Description: "Business Analyst agent cho software team"
Instructions: |
  Bạn là BA Agent. Đọc file agents/ba.md để hiểu role.
  Khi user gọi "analyze BRIEF-00001", thực hiện workflow trong commands/analyze-requirements.md
Knowledge:
  - .claude/agents/ba.md
  - .claude/commands/analyze-requirements.md
  - .claude/templates/BRIEF-template.md
```

**Tạo 7 Custom GPTs** - mỗi agent một GPT.

**Workflow:**
```
User: "@BA analyze BRIEF-00001"
GPT reads brief → generates DRAFT-REQ → user copy vào file system
```

### Option 2: Single ChatGPT + Role Switching

```
Prompt: "Switch to BA Agent role (read .claude/agents/ba.md). 
         Analyze BRIEF-00001 và tạo DRAFT-REQ."
```

**Hạn chế:** Không truy cập trực tiếp file system → cần copy-paste.

---

## 🔄 Port sang Cursor / Windsurf

### Cấu trúc mới:

```
# Claude Code
.claude/
  ├── CLAUDE.md
  ├── agents/
  └── commands/

# Cursor
.cursorrules
.cursor/
  ├── CURSOR.md
  ├── agents/
  └── rules/

# Windsurf
.windsurfrules
.windsurf/
  ├── CASCADE.md
  ├── agents/
  └── flows/
```

### Adapt commands:

```markdown
# Claude Code syntax
/analyze-requirements BRIEF-00001

# Cursor syntax
@workspace /analyze BRIEF-00001

# Windsurf Cascade
> Flow: analyze-requirements BRIEF-00001
```

**Migration steps:**
1. Copy `.claude/` → `.cursor/` hoặc `.windsurf/`
2. Đổi tên entry point
3. Adapt command syntax theo docs của platform
4. Test từng workflow

---

## 🔄 Port sang GitHub Copilot

### ✅ Version đầy đủ đã có sẵn!

Phiên bản GitHub Copilot đã được tạo tại `.github-copilot/`:

```
.github-copilot/
├── copilot-instructions.md     ← Entry point (tương đương CLAUDE.md)
├── PROJECT-PROFILE.md          ← Project configuration
├── agents/                     ← 7 agent definitions
│   ├── ba.md
│   ├── pm.md
│   ├── developer.md
│   ├── techlead.md
│   ├── tester.md
│   ├── analyst.md
│   └── docsync.md
├── commands/                   ← 24 slash commands
│   ├── setup-project.md
│   ├── analyze-requirements.md
│   ├── plan-sprint.md
│   └── ... (21 more)
├── templates/                  ← File templates
│   ├── ADR-template.md
│   ├── BRIEF-template.md
│   ├── BUG-template.md
│   └── TASK-template.yaml
└── workspace/                  ← Working directories
    ├── requirements/input/
    ├── tasks/
    ├── sprints/
    ├── bugs/
    ├── analysis/
    └── reviews/
```

### Cách sử dụng với GitHub Copilot Chat

**Trong VS Code:**
1. Mở Copilot Chat (Ctrl+Shift+I hoặc Cmd+Shift+I)
2. Sử dụng `@workspace` để chat với context của project
3. Gọi các workflow bằng cách yêu cầu Copilot thực hiện

**Ví dụ commands:**
```
@workspace Hãy thực hiện /analyze-requirements cho BRIEF-00001
@workspace Hãy chạy /plan-sprint để tạo task từ REQ đã được phê duyệt
@workspace Hãy thực hiện /implement-task TASK-00002 theo quy trình TDD
@workspace Hãy chạy /techlead-review TASK-00002
@workspace Hãy tạo /smoke-test v1.0.0
```

### Option bổ sung: VS Code Extension

Nếu muốn tích hợp sâu hơn, có thể tạo extension:

```typescript
// extension.js
const commands = {
  'agentic.analyzeRequirements': async (briefId) => {
    const agentDef = readFile('.github-copilot/agents/ba.md');
    const commandDef = readFile('.github-copilot/commands/analyze-requirements.md');
    
    const prompt = `${agentDef}\n\n${commandDef}\n\nBRIEF ID: ${briefId}`;
    const response = await copilot.chat(prompt);
    
    writeFile(`DRAFT-REQ-${briefId}.md`, response);
  }
};
```

**Activate:**
```
Cmd+Shift+P → "Agentic: Analyze Requirements"
```

---

## 🔄 Port sang Local LLMs (LM Studio, Ollama)

### Sử dụng framework wrapper

#### Option 1: CrewAI

```python
from crewai import Agent, Task, Crew

ba_agent = Agent(
    role="BA",
    goal="Phân tích brief và tạo spec",
    backstory=open('.claude/agents/ba.md').read(),
    allow_delegation=False
)

task = Task(
    description=open('.claude/commands/analyze-requirements.md').read(),
    agent=ba_agent
)

crew = Crew(agents=[ba_agent], tasks=[task])
result = crew.kickoff()
```

#### Option 2: LangGraph

```python
from langgraph.graph import StateGraph

def ba_node(state):
    agent_def = read_file('.claude/agents/ba.md')
    brief = read_file(f'BRIEF-{state["brief_id"]}.md')
    
    prompt = f"{agent_def}\n\nBrief:\n{brief}\n\nTạo DRAFT-REQ."
    response = llm.invoke(prompt)
    
    write_file(f'DRAFT-REQ-{state["brief_id"]}.md', response)
    return state

workflow = StateGraph()
workflow.add_node("ba", ba_node)
```

---

## 📊 So sánh Platform

| Platform | Ease of Port | Native Support | File Access | Git Ops | Rating |
|----------|--------------|----------------|-------------|---------|--------|
| **Claude Code** | ✅ Original | ✅ Full | ✅ Direct | ✅ Direct | ⭐⭐⭐⭐⭐ |
| **GitHub Copilot** | ✅ Ready | ✅ Full | ✅ Direct | ✅ Direct | ⭐⭐⭐⭐⭐ |
| **Cursor** | ✅ Easy | ✅ Good | ✅ Direct | ✅ Direct | ⭐⭐⭐⭐⭐ |
| **Windsurf** | ✅ Easy | ✅ Good | ✅ Direct | ✅ Direct | ⭐⭐⭐⭐ |
| **ChatGPT** | ⚠️ Medium | ⚠️ Limited | ❌ Copy-paste | ❌ Manual | ⭐⭐⭐ |
| **Local LLM** | ❌ Hard | ❌ Framework needed | ✅ Via framework | ✅ Via framework | ⭐⭐ |

---

## 🎯 Recommended Approach: Platform-Agnostic Version

### Tạo Universal Format

```
agentic-software-team/
├── config.yaml              ← Platform detection
├── agents/                  ← Universal agent defs
├── workflows/               ← Platform-agnostic workflows
│   ├── analyze-requirements.yaml
│   └── implement-task.yaml
├── adapters/                ← Platform-specific adapters
│   ├── claude-code/
│   ├── cursor/
│   ├── chatgpt/
│   └── copilot/
└── core/                    ← Shared logic
```

### Config example:

```yaml
# config.yaml
platform: auto-detect   # claude-code | cursor | chatgpt | copilot
agents:
  - ba
  - pm
  - techlead
  - developer
  - tester
  - analyst
  - docsync

adapters:
  claude-code:
    command_prefix: "/"
    config_file: ".claude/CLAUDE.md"
  cursor:
    command_prefix: "@"
    config_file: ".cursorrules"
  chatgpt:
    mode: "custom-gpt"
    knowledge_base: true
```

---

## 🚀 Quick Start cho từng Platform

### Claude Code (hiện tại)
```bash
# Đã setup sẵn
/setup-project
```

### GitHub Copilot (đã có sẵn!)
```bash
# Phiên bản đầy đủ đã được tạo
# Xem .github-copilot/copilot-instructions.md

# Sử dụng với Copilot Chat:
@workspace Hãy thực hiện /setup-project
```

### Cursor
```bash
cp -r .claude .cursor
mv .cursor/CLAUDE.md .cursor/CURSOR.md
# Edit slash commands → @ commands
code .cursorrules
```

### ChatGPT
```bash
# Tạo 7 Custom GPTs tại chat.openai.com/gpts/editor
# Upload từng agent definition vào Knowledge
```

### GitHub Copilot
```bash
# Install extension (nếu có)
code --install-extension agentic-software-team
# Hoặc dùng Copilot Chat với manual prompts
```

---

## ✅ Core Principles (platform-independent)

1. **Agents là personas** - markdown files mô tả role
2. **Workflows là steps** - có thể execute bằng bất kỳ LLM nào
3. **State trong Git** - không phụ thuộc LLM
4. **Templates là cấu trúc dữ liệu** - reusable
5. **Human checkpoints** - bất biến

---

*Hệ thống được thiết kế sao cho **core logic** tách biệt khỏi **execution platform**.*
