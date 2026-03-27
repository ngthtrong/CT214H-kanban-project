# Agentic Software Team: Xây dựng nhóm phát triển phần mềm bằng AI trên Claude Code

Sáu ngày. Đó là khoảng thời gian tôi đã bỏ ra — sáu ngày lặp đi lặp lại việc trao đổi và tinh chỉnh, để rồi kết quả cuối cùng là một nhóm phát triển phần mềm đa tác nhân (multi-agent) hoàn chỉnh chạy trên Claude Code.

Không phải là một chatbot. Không phải là công cụ tự động hoàn thiện mã (code autocomplete). Đây là một đội ngũ thực thụ — bao gồm một BA, một PM, một Tech Lead, một Developer, một Tester, một Analyst (Chuyên viên phân tích), và một Agent Doc Sync (Đồng bộ tài liệu) — mỗi thành viên có một vai trò rõ ràng, các giới hạn an toàn (guardrails) riêng, và khả năng tiếp nhận một bản mô tả sản phẩm (product brief) thô để đưa nó đi đến tận bước release (phát hành) được gắn tag trên nhánh `main`. Trong một kho lưu trữ (repo) Git thực tế. Với đầy đủ các bài test, PR (Pull Request), code review, báo cáo lỗi (bug report), và các cổng phê duyệt qua smoke test (kiểm thử cơ bản).

Tôi gọi nó là **Agentic Software Team** (Đội ngũ Phần mềm AI - phiên bản v7 sau sáu ngày lặp lại quá trình phát triển, tính đến thời điểm bài viết này).

Tôi muốn trình bày chính xác những gì đã diễn ra ở đây vì tôi cho rằng điều đó rất quan trọng: Tôi không viết hệ thống này một mình, và tôi cũng sẽ không giả vờ rằng mình đã làm thế. Tôi thiết kế hệ thống, đưa ra các quyết định kiến trúc, phản biện lại khi có điểm sai sót, và giữ cho định hướng luôn rõ ràng. Claude đã viết phần triển khai thực tế (implementation) — từng file agent, từng file lệnh (command), từng template, file trình chiếu PPTX, tài liệu khái niệm (concept doc), và cả bài viết này. Thứ tôi đóng góp là khả năng phán đoán kỹ thuật, kiến thức chuyên môn từ hơn 20 năm làm phần mềm, và sự kỷ luật để liên tục đặt câu hỏi *"nhưng chuyện gì sẽ xảy ra nếu phần này bị lỗi?"* cho đến khi nhận được những câu trả lời thỏa đáng.

Đánh giá một cách trung thực thì: Claude Code là một cộng sự cực kỳ đắc lực nếu bạn mang đến chuyên môn để định hướng cho nó. Nếu không biết một git workflow chuẩn trông như thế nào, TDD thực sự yêu cầu những gì, truy vấn N+1 là gì và khi nào chúng gây rắc rối, điều gì làm cho smoke test khác biệt với E2E test — nếu thiếu tất cả những điều đó, thứ bạn nhận được chỉ là một mô hình trông có vẻ giống một hệ thống nhưng thực chất không thể vận hành bền vững. Sự phán đoán là của tôi. Còn việc thực thi được chia sẻ cho cả hai.

## Tôi thực sự đang giải quyết bài toán gì?

Dưới đây là lý do thực sự cho sự tồn tại của hệ thống này.

Các trợ lý AI lập trình rất ấn tượng. Chúng viết code nhanh, hiểu ngữ cảnh, và có thể tham gia vào những cuộc thảo luận chuyên sâu đáng ngạc nhiên về kiến trúc. Nhưng nếu để chúng tự hoạt động tự do, chúng sẽ thiếu kỷ luật theo những cách gây hậu quả nghiêm trọng khi hệ thống mở rộng quy mô (at scale):

*   Chúng viết code trực tiếp lên nhánh `main`, làm hỏng lịch sử git.
*   Chúng triển khai các task chưa từng được review hoặc thậm chí chưa được commit vào repo.
*   Chúng bỏ qua việc viết test, hoặc chỉ coi đó là việc phụ để làm sau (afterthought).
*   Chúng coi các tài liệu kế hoạch (spec, bảng chia task, kế hoạch sprint) như những file local tạm thời, sẽ biến mất giữa các phiên làm việc.
*   Chúng không quan tâm đến các truy vấn N+1, thiếu index (chỉ mục database), hay điều gì sẽ xảy ra khi một bảng có cả triệu dòng.

Nếu xét riêng lẻ, mỗi yếu tố trên chỉ là một sự phiền toái nhỏ. Nhưng khi gộp lại, trong một đội ngũ đang phụ thuộc vào AI để tăng tốc độ, chúng trở thành sự rò rỉ rả rích của "nợ kỹ thuật" (technical debt) và cuối cùng sẽ nhấn chìm cả dự án.

Câu hỏi tôi luôn tự đặt ra là: điều gì sẽ xảy ra nếu chính hệ thống AI tự thực thi sự kỷ luật đó? Không phải đóng vai trò là một người review ở cuối quy trình, mà được nhúng chặt vào từng bước phát triển.

## Kiến trúc của một đội ngũ kỷ luật

Hệ thống này nằm trọn trong thư mục `.claude/` tại thư mục gốc (root) của bất kỳ dự án nào. Nó chỉ đơn thuần là các file markdown — định nghĩa persona (tính cách) của các agent và các slash command (lệnh bắt đầu bằng gạch chéo) để Claude Code đọc như các chỉ thị. Không cần hạ tầng cơ sở (infrastructure), không cần triển khai (deployment), không có lời gọi API nào nằm ngoài những gì Claude Code vốn có. Bạn chỉ cần chạy lệnh `claude` trong terminal của mình, và cả đội ngũ đã ở đó.

Tám agent, mỗi agent mang một trách nhiệm duy nhất:

*   **Agent BA (Business Analyst):** Đọc các bản mô tả sản phẩm và viết các bản đặc tả yêu cầu (requirement specs) kèm theo các tiêu chí nghiệm thu (acceptance criteria) có thể test được dưới định dạng *Given/When/Then*. Nó đánh dấu các từ ngữ mơ hồ, ghi chú các câu hỏi còn bỏ ngỏ, và tuyệt đối không bao giờ tự bịa ra những yêu cầu không được giao. Nó viết các bản nháp (drafts) — và PO (Product Owner) sẽ phê duyệt bằng cách đổi tên file.
*   **Agent PM (Project Manager):** Phân rã các spec đã được phê duyệt thành các chuỗi task. Mỗi tính năng được chia thành ba task: một task unit-test (viết test đang fail trước), một task tính năng (triển khai code cho đến khi test pass), và một task E2E test (kiểm thử dựa trên tiêu chí nghiệm thu). Nó tự động gán ID theo trình tự, xử lý các phụ thuộc (dependencies), và — điểm mấu chốt — commit mọi thứ vào nhánh `develop` trước khi bất kỳ thao tác code nào được bắt đầu.
*   **Agent Tech Lead:** Viết các Tài liệu Quyết định Kiến trúc (ADR - Architecture Decision Records), duy trì các quy tắc giới hạn khi code, review các Pull Request, và xử lý việc quản lý contract chéo trong các dự án multi-stack. Hiện tại, mọi ADR mà nó viết đều bắt buộc phải có một phần Yêu cầu phi chức năng (Non-functional requirements): tác động đến hiệu năng, khả năng mở rộng (scalability), bảo mật, độ tin cậy, và khả năng giám sát. Không được tùy chọn. Không được điền sau. Bắt buộc phải có trước khi PR được duyệt.
*   **Agent Developer:** Triển khai các tính năng và sửa lỗi với kỷ luật TDD nghiêm ngặt — viết test trước, viết code sau, và phải vượt qua cổng đánh giá test coverage (độ bao phủ mã, tối thiểu 80%) trước khi sẵn sàng cho bước review. Sau khi test pass, nó chạy một đợt quét hiệu năng (performance scan): `grep` tìm các vòng lặp chứa các lời gọi DB bên trong (truy vấn N+1), kiểm tra xem các cột `WHERE/JOIN` mới đã được đánh index hay chưa, đảm bảo các truy vấn lấy danh sách đều có `LIMIT`. Những lỗi tốn ít công sức sẽ được sửa ngay. Bất cứ lỗi nào đòi hỏi phải refactor nhiều file sẽ được ghi nhận là nợ kỹ thuật (tech debt) và ghi chú lại trong PR. Nó không bao giờ can thiệp trực tiếp vào `main` hay `develop` — luôn làm việc trên nhánh `feature`, và nhánh này luôn được tạo ra từ `develop`.
*   **Agent Tester:** Kiểm định các task đã hoàn thành dựa trên tiêu chí nghiệm thu, viết các E2E test, và — trước khi release — chạy bộ smoke test trên một môi trường live (môi trường đang chạy thực tế). Nó quản lý danh sách kịch bản của smoke test (tối đa 10 kịch bản, chỉ tập trung vào các luồng người dùng trọng yếu, không dùng mock cho ứng dụng đang chạy thực tế). PO sẽ ký duyệt (sign-off) trên báo cáo smoke test trước khi lệnh `/release` được phép chạy tiếp.
*   **Agent Codebase Analyst:** Thực hiện việc khám phá hệ thống cũ (brownfield discovery) sâu sát qua 10 giai đoạn: vẽ sơ đồ các module, trích xuất business logic bị chôn vùi trong code, xác định các vùng dễ gãy hỏng (fragile zones), đo lường test coverage, làm tài liệu bề mặt API, và phát hiện sự sai lệch (drift) giữa tài liệu và code hiện tại. Chạy agent này một lần trên dự án mới, bạn sẽ có bảy tài liệu phân tích "sống" mà sau đó Agent Doc Sync sẽ chịu trách nhiệm cập nhật.
*   **Agent Doc Sync:** Chạy sau mỗi sprint hoặc sau mỗi PR được merge, phát hiện sự sai lệch giữa tài liệu và code, đồng thời bắn cảnh báo khi các tài liệu phân tích không còn phản ánh chính xác thực tế.

30 slash command bao trùm toàn bộ vòng đời phát triển:

```text
/setup-project        /detect-stack         /discover-codebase
/analyze-requirements /plan-sprint          /write-unit-tests
/implement-task       /check-coverage       /run-tests
/create-pr            /techlead review      /merge-pr
/smoke-test           /release              /report-bug
/triage-bug           /verify-fix           /bug-status
/branch-status        /cleanup-branches     /scan-untracked
...
```

## Những thứ khó khăn hơn tôi tưởng

**Các tài liệu kế hoạch (planning artifacts) phải nằm trong git trước khi bắt tay vào code.**

Nhìn lại thì điều này nghe có vẻ hiển nhiên. Nhưng hành vi mặc định của các AI agent là viết file xuống ổ đĩa rồi ngay lập tức lấy ra sử dụng. Các file spec tồn tại ở local. Các file YAML của task nằm ở local. Kế hoạch sprint cũng nằm ở local. Không có thứ gì được commit. Khi phiên làm việc kết thúc hoặc context (ngữ cảnh) bị reset, toàn bộ trạng thái kế hoạch cũng "bay màu".

Cách khắc phục là buộc mọi lệnh planning phải commit các tài liệu của chúng vào nhánh `develop` ngay sau khi viết, và làm cho lệnh `/implement-task` từ chối việc tạo nhánh cho đến khi nó xác minh được file YAML của task đã thực sự nằm trong git. Chỉ với thay đổi duy nhất này, toàn bộ nhóm lỗi kiểu "viết code dựa trên một task mà chẳng ai khác thấy được" đã bị loại bỏ hoàn toàn.

**Bảo vệ nhánh (Branch protection) không chỉ là một cấu hình, nó là một hành vi.**

Các quy tắc bảo vệ nhánh Git trên GitHub ngăn chặn các thao tác push trực tiếp lên những nhánh được bảo vệ. Nhưng các AI agent không đi qua API của GitHub — chúng sử dụng `git CLI` dưới local. Không có gì có thể ngăn chặn lệnh `git commit -m "feat: ..."` trên nhánh `main` trừ khi chính bản thân agent đó được chỉ thị là không được làm vậy.

Giờ đây, mọi lệnh trong hệ thống đều có một "lính canh nhánh" (branch guard) chạy trước khi nó kịp can thiệp vào bất kỳ file nào. Các lệnh planning (`/analyze-requirements`, `/plan-sprint`) sẽ phát hiện nếu chúng đang ở trên nhánh `main` và tự động chuyển sang `develop`. Các lệnh lập trình (implementation) sẽ báo lỗi nếu đang ở nhánh `main`. Các lệnh phát hành (release) yêu cầu rõ ràng phải ở trên nhánh `develop` và sẽ không âm thầm tự động chuyển nhánh.

**Các file không được theo dõi (untracked files) của con người là một nguồn nhiễu ngầm.**

Tôi đã thêm một lệnh `/scan-untracked` để chạy trước mỗi lượt commit kế hoạch. Lệnh này phân loại tất cả các file untracked: các tài liệu của agent (tự động include), các file trong gitignore (bỏ qua), và các file do con người tạo ra (hỏi ý kiến PO). Dữ liệu test (dry-run), script seed data, các file ghi chú nháp, các file ghi đè config cục bộ — tất cả những thứ này giờ đây hiển thị thành các quyết định rõ ràng: đưa vào commit này, commit riêng lẻ, thêm vào `.gitignore`, hoặc tạm thời bỏ qua.

Lần đầu tiên chạy nó trên một dự án thật, nó tìm ra sáu file mà tôi đã quên béng. Một trong số đó chứa một API key bị hardcode mà tôi đang dùng để test dưới local. Nếu không có lệnh này, nó đã bị commit một cách thầm lặng.

**Cổng release cần phải được test trên môi trường live thực tế.**

Unit test và E2E test thường chạy dựa trên các test double và mock (dữ liệu giả lập). Chúng là cần thiết nhưng chưa đủ. Lệnh `/smoke-test` khởi động toàn bộ môi trường (có thể cấu hình qua `PROJECT-PROFILE.md` — ví dụ: `docker-compose up`, kiểm tra các URL health check, chạy lệnh test), thực thi các kịch bản test nhắm vào ứng dụng đang chạy thực tế, dọn dẹp hệ thống (tear down), viết một báo cáo có chữ ký, và đợi PO ký duyệt trước khi lệnh `/release` được phép merge nhánh `develop` vào `main`. Mã băm (commit hash) trong báo cáo sẽ được đối chiếu chéo với `HEAD` hiện tại của nhánh `develop` — nếu ai đó merge một bản hotfix sau khi smoke test diễn ra, chữ ký duyệt sẽ bị vô hiệu và bắt buộc phải chạy test lại.

## Một Sprint thực tế diễn ra như thế nào

Đây là chuỗi thực thi thực tế từ một lần chạy test gần đây trên một dự án Go + TypeScript (ImgResizer.NET đã trở thành "chuột bạch"):

```text
/setup-project                 → .claude/ được commit vào develop
/detect-stack                  → STACK.md được viết (Go + TypeScript, openapi contract)
/discover-codebase             → phân tích 10-giai-đoạn, PROJECT-PROFILE.md được viết
/update-agents                 → cập nhật guardrails với các lệnh test thực tế

# Viết một bản brief trong .claude/workspace/requirements/input/BRIEF-00001.md
/analyze-requirements          → BA viết DRAFT-REQ-00001.md
                               ← PO đổi tên thành REQ-00001.md (phê duyệt)
/plan-sprint                   → Chuỗi 3 task được commit vào develop

/write-unit-tests TASK-00001   → các test đang fail được viết (TDD chu kỳ đỏ)
/implement-task TASK-00002     → code tính năng + quét lỗi N+1 + đánh giá cổng coverage
/run-tests TASK-00003          → viết các bài test E2E
/create-pr TASK-00002
/techlead review TASK-00002    → Đã vượt qua checklist NFR, được duyệt (approved)
/merge-pr TASK-00002           → squash merge, dọn dẹp nhánh, đồng bộ tài liệu (doc sync)

/smoke-test v1.0.0             → khởi động toàn bộ môi trường, 4 kịch bản đều pass
                               ← PO ký duyệt ngay trong báo cáo
/release v1.0.0                → chạy bộ test hồi quy (regression) → xác nhận YES → develop→main → gắn tag
```

Tổng cộng: 15 lệnh. Tổng thời gian gõ phím: có lẽ khoảng hai phút. Phần thời gian còn lại là dành cho việc đọc và phê duyệt.

## Các cổng kiểm soát có con người tham gia (Human-in-the-loop)

Hệ thống này có chín điểm mà tại đó nó sẽ dừng lại và chờ quyết định từ con người:

1. **Review Stack** — xác minh cấu hình công nghệ (stack) mà AI phát hiện là chính xác.
2. **Phê duyệt Spec** — đổi tên `DRAFT-REQ` thành `REQ` để duyệt từng bản đặc tả.
3. **File Untracked** — quyết định cần làm gì với mỗi nhóm file trước khi thực hiện commit kế hoạch.
4. **Review ADR** — đọc các quyết định kiến trúc trước khi quá trình code bắt đầu.
5. **Bug P1** — quyết định xem có nên thực hiện hotfix ngay lập tức hay lùi lại (tạm dừng sprint).
6. **Ký duyệt Smoke test** — xác nhận tất cả các kịch bản đều vượt qua trên môi trường live.
7. **Xác nhận Release** — gõ `YES` trước khi `develop` được merge vào `main`.

Đây là kim chỉ nam mà tôi luôn giữ vững trong suốt quá trình xây dựng hệ thống. Mục tiêu ở đây không bao giờ là loại bỏ con người khỏi quy trình phát triển phần mềm. Mục tiêu là giải phóng con người khỏi những công đoạn không cần đến họ — những công việc máy móc, những thao tác lặp đi lặp lại, những phần mà thời gian của con người thực sự bị lãng phí — và tập trung năng lực phán đoán của con người vào những nơi thực sự có ý nghĩa.

## Skills: Gói hóa quy trình thành đơn vị tái sử dụng

Một trong những bổ sung quan trọng nhất cho hệ thống là **Skills** — các đơn vị workflow có thể tái sử dụng, đóng gói một chuỗi hành động phức tạp thành một lời gọi đơn giản.

### Skill là gì?

Skill là một file `SKILL.md` nằm trong thư mục riêng, định nghĩa:
- **Tên và mô tả** — để AI và người dùng hiểu mục đích
- **Argument-hint** — gợi ý tham số đầu vào
- **When to Use** — điều kiện kích hoạt
- **Procedure** — chuỗi bước thực hiện
- **Required References** — các file tham chiếu cần thiết

### Các Skill có sẵn

* **agentic-sprint** — Chạy toàn bộ quy trình sprint từ requirements đến release, đảm bảo tuân thủ đúng thứ tự lệnh và các checkpoint.
* **agentic-brownfield** — Thực hiện phân tích brownfield 10 giai đoạn trên codebase có sẵn, tạo tài liệu phân tích và báo cáo drift.

### Cấu trúc thư mục Skills

```
# Claude Code
.claude/skills/
├── agentic-sprint/
│   └── SKILL.md
├── agentic-brownfield/
│   └── SKILL.md
└── [custom-skill]/
    └── SKILL.md

# GitHub Copilot (VSCode)
.github-copilot/skills/
├── agentic-sprint/
│   └── SKILL.md
└── [custom-skill]/
    └── SKILL.md

# GitHub Native (Copilot Chat)
.github/skills/
├── agentic-sprint/
│   └── SKILL.md
├── agentic-brownfield/
│   └── SKILL.md
└── [custom-skill]/
    └── SKILL.md
```

### Tạo Skill mới với `/create-skill`

Lệnh `/create-skill` cho phép tạo skill mới:

```text
/create-skill my-custom-workflow
```

Lệnh này sẽ:
1. Tạo thư mục skill với tên đã cho
2. Tạo file `SKILL.md` theo template chuẩn
3. Hướng dẫn cách điền nội dung
4. Commit vào `develop` (nếu được phê duyệt)

### Format SKILL.md

```yaml
---
name: skill-name
description: 'Mô tả ngắn gọn về skill'
argument-hint: 'Gợi ý tham số đầu vào'
user-invocable: true
---
# Tên Skill

## When to Use
- Use case 1
- Use case 2

## Procedure
1. Bước 1
2. Bước 2
...

## Required References
- `.claude/commands/relevant-command.md`
- `.claude/agents/relevant-agent.md`
```

## Bước tiếp theo là gì

Dưới đây là một vài thứ vẫn đang nằm trong danh sách cần làm của tôi:

* Một lệnh dashboard đúng nghĩa hiển thị tổng quan — để xem toàn bộ trạng thái của một sprint chỉ trong nháy mắt: tất cả các task, trạng thái, các con số coverage, bug đang open, gói gọn trong một màn hình.
* Tích hợp chặt chẽ hơn với GitHub Issues và PR templates để các file YAML trong workspace và trạng thái trên GitHub luôn được đồng bộ.
* Tạo ra một phiên bản dành cho các quy trình làm việc phi kỹ thuật (non-engineering workflows) — như khám phá sản phẩm (product discovery), dây chuyền sản xuất nội dung, các sprint phân tích dữ liệu — cùng một tính kỷ luật, nhưng áp dụng cho chuyên môn khác.

