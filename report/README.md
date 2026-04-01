# Report Agent Guide

## 1) Muc tieu
Tai lieu nay huong dan AI agents lam viec an toan trong thu muc `report/` cua du an CT214H-kanban-project.

Muc tieu:
- Hieu cau truc module cua bao cao LaTeX.
- Chinh sua dung file, dung pham vi.
- Build va kiem tra log sau moi thay doi.
- Giu chat luong so do (mui ten, nhan, bo cuc) de khong de len noi dung.

## 2) Cau truc thu muc

Entrypoint:
- `main.tex`

Cac nhom thu muc:
- `preamble/`
  - `packages.tex`: package, style, TikZ libs, page layout.
  - `metadata.tex`: metadata (ten de tai, giang vien, ngay nop...).
  - `macros.tex`: macro dung chung (`\code`, `\screenshotplaceholder`, `\diagramnote`).
- `frontmatter/`
  - `cover.tex`: trang bia.
- `chapters/`
  - `01_introduction/` ... `06_conclusion_future/`
  - Moi chuong co file `chapter.tex` de include cac section con.
- `diagrams/`
  - Tat ca so do TikZ (`component-interaction.tex`, `erd-overview.tex`, `usecase-overview.tex`, `uc1..uc6`, ...).
- `appendices/`
  - `appendix_api_matrix.tex`, `appendix_screenshot_plan.tex`.
- `assets/screenshots/`
  - Noi dat anh screenshot thuc te de thay placeholder.
- `build/`
  - Artifact sinh ra khi build (khong chinh tay).

## 3) Luong include
`main.tex` include theo thu tu:
1. preamble (`packages`, `metadata`, `macros`)
2. frontmatter (`cover`)
3. TOC/LOF/LOT
4. chuong 01 -> 06
5. appendix

Nguyen tac:
- Khong viet noi dung dai truc tiep vao `main.tex`.
- Noi dung chuong dat trong cac file section con va include qua `chapter.tex`.

## 4) Chinh sua dung cho tung loai yeu cau
- Doi thong tin de tai, giang vien, ngay nop: sua `preamble/metadata.tex`.
- Doi bo cuc/noi dung trang bia: sua `frontmatter/cover.tex`.
- Cap nhat noi dung hoc thuat: sua file section trong `chapters/**`.
- Them/chinh so do: sua file trong `diagrams/`.
- Them bang phu luc: sua file trong `appendices/`.
- Anh minh hoa: dat file vao `assets/screenshots/` va dung macro `\screenshotplaceholder` neu can.

## 5) Rang buoc quan trong cho agent
- Chi sua file can thiet, toi thieu thay doi.
- Khong doi ten file/duong dan dang duoc `\input` neu khong cap nhat dong bo.
- Khong xoa/doi `\label` va `\caption` mot cach vo co.
- Khong sua file sinh ra trong `build/` (`.aux`, `.log`, `.toc`, `.out`, PDF artifact...).
- Khong chinh sua thu muc ngoai `report/` tru khi user yeu cau ro rang.
- Giu ngon ngu report nhat quan (hien tai la tieng Anh cho noi dung hoc thuat).

## 6) Quy uoc rieng cho so do TikZ
Ap dung cho tat ca file trong `diagrams/`:
- Mui ten khong di xuyen qua box noi dung neu co the di duong ngan hon o ben ngoai.
- Nhan mui ten dat o vi tri khong de len node text.
- Uu tien duong noi truc giao, gon, de doc; han che duong vong du thua.
- Neu co nhanh loi (`no/conflict/forbidden`), uu tien route qua bypass lane trai/phai.
- Giu style thong nhat voi file hien co (`arr`, `rel`, `assoc`, `dep`, ...).

## 7) Build va kiem tra
Chay trong thu muc `report/`:

```powershell
xelatex -interaction=nonstopmode -halt-on-error -output-directory=build/ main.tex
xelatex -interaction=nonstopmode -halt-on-error -output-directory=build/ main.tex
```

Quet warning/error:

```powershell
if (Test-Path .\build\main.log) {
  Select-String -Path .\build\main.log -Pattern "^!|LaTeX Warning|Package .* Warning|Overfull|Underfull|Undefined|Error" |
  ForEach-Object { $_.Line }
}
```

Luu y:
- Dong `Package: infwarerr ... info/warning/error messages` la thong tin package, khong phai loi build.

## 8) Checklist truoc khi ket thuc
- Da build 2 pass thanh cong.
- Khong co loi `!` trong log.
- Warning overfull/underfull da duoc danh gia va xu ly neu lien quan thay doi vua lam.
- PDF dau ra cap nhat tai `build/main.pdf`.
- Tom tat cho user can neu ro: file nao da sua, ly do, ket qua build.

## 9) Scope safety
Neu thay doi user yeu cau co the anh huong file ngoai `report/`, phai:
1. Neu ro pham vi voi user.
2. Uu tien tach thay doi theo commit logic (noi dung report vs code app).
3. Khong tu y hoan tac thay doi cua nguoi dung o file khong lien quan.
