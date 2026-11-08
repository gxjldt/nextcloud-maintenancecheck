# AGENTS.md

本仓库是 [MaintenanceCheck](https://github.com/aSoftwareByDesignRepository/nextcloud-maintenancecheck) 的公司二开 fork（Nextcloud PHP 应用，上游 AGPL-3.0）。

## 目录约定

先分清两类目录，规则完全不同。

### A. 框架固定，不要移动、不要改名

Nextcloud 应用加载器按固定路径找文件，`composer.json` 的 PSR-4 也只映射了 `lib/`：

| 目录 | 作用 | 约束 |
|---|---|---|
| `appinfo/` | info.xml、routes.php、应用清单 | 路径由框架写死 |
| `lib/` | 全部 PHP 类，命名空间 `OCA\MaintenanceCheck\` | 新增文件必须与 PSR-4 路径一致 |
| `templates/` | 服务端渲染模板 | 按文件名查找 |
| `css/` `js/` | 前端资源 | 由模板和 `Util::addScript` 引用 |
| `l10n/` | 翻译，`<lang>.json` + `.js` | 文件名即语言标签，中文用 `zh_CN` |
| `img/` | 应用图标 | info.xml 引用 |
| `data/` | 随包分发的数据（procedure-packs） | 运行时读取路径 |

### B. 工程侧，可以按需要整理

| 路径 | 放什么 |
|---|---|
| `tests/Unit` `tests/Integration` | PHPUnit，由 `phpunit.xml` 指定 |
| `tests/Mutation` | Infection 配置与本地输出（输出已 gitignore） |
| `tests/e2e` | Playwright 用例 + `global-setup` |
| `tests/fixtures` | 测试数据（license 样本、openapi.json） |
| `e2e/` 根目录 | 独立截图采集脚本，不在 Playwright 主用例集内 |
| `scripts/` | 开发与发布辅助脚本 |
| `docs/` | 设计与对接说明 |
| `screenshots/` | 应用商店展示图 |

## 硬规则

1. **不要提交构建产物**。`build/release/`、`release/` 下的 tar.gz 一律走 GitHub Release。`.gitignore` 已覆盖这两个路径。
2. **不加大小写只差一位的目录**。`tests/Fixtures` 和 `tests/fixtures` 在 macOS/Windows 上会合并成一个，Linux 上是两个 —— 曾经因此留了一组无人引用的孤立文件。
3. **删除文件前先确认有引用**。`grep -rn "<文件名>" . --exclude-dir=.git --exclude-dir=vendor`，无命中才删。
4. **测试脚本改名要同步改**：`phpunit.xml`、`composer.json` 的 `scripts`、`package.json` 的 `scripts`、`playwright.config.js` 的 `testMatch`/`globalSetup`、`.gitignore` 里的输出路径。

## 前端一律用 Nextcloud core 的结构与度量

前端按 Nextcloud 自己的实现走，不要在应用里另建一套设计系统。判断标准：能指到 core 某条规则，就照抄 core；指不到才写本仓 CSS，并在注释里写清「core 没有覆盖到哪一点」。

| 部位 | 用 core 的什么 | 本仓只剩什么 |
|---|---|---|
| 侧栏 | `#app-navigation.app-navigation-personal` + `li > a.nav-entry-link`；折叠是 `li.collapsible` + `button.collapse` + `open`；移动抽屉是 `body.snapjs-left` + `#app-navigation-toggle`；底部固定项是 `li.pinned` | 内联 SVG 图标对齐（core 只给 `img` 写样式） |
| 页面头 | 一个 `h1`（20px 加粗）+ 右侧动作行；提示语是普通灰色小字 | 无：不再有图标井、徽章条 |
| 列表工具栏 | Files 那样的一行控件，不套卡片 | `.mn-toolbar` 一行 flex + `.mn-filter-field` 字段 |
| 表格 | 表头 bold 透明底、行间 1px `--color-border`、无斑马纹无外框 | `.mn-list-section` 只做扁平化，不再画框 |
| 表单/按钮 | `core/css/inputs.css`：高度 `var(--default-clickable-area)`、2px `--color-border-maxcontrast`、按钮 `calc((area - 1lh)/2) 12px`、8px 圆角、bold | 语义类名 `.mn-btn--primary/secondary/danger` 仍在，只复述 core 配色 |
| Toast | `window.OCP.Toast`（→ `@nextcloud/dialogs`） | 去重（同文案重置计时器）+ 可选动作按钮 |
| 弹窗 | core 没有兼容的表单弹窗 API，结构自留；**度量照 NcModal/NcDialog**：600/400/900px、`--border-radius-container`、`0 0 40px var(--color-box-shadow)`、标题 21px、动作区 gap 6px | 结构 + 焦点陷阱 |

硬性禁止：

- 不改写 core 已有的行高、圆角、主色选中态、抽屉断点（曾经写过 280/240px 侧栏宽、42px 行高、44px 一刀切按钮，都与 core 打架）
- 不新增「卡片套卡片」的列表框：列表直接在页面底色上
- 不在 CSS 里写裸色值（`tests/js/css-theme-guard.test.mjs` 会拦），保留 `var()` 回退
- 不在应用里重建 core 的能力（toast、confirm 提示、抽屉、折叠）

改完动 CSS 形状的提交要同步断言：`tests/Unit/Support/AzcShellParityContractTest.php`、`DesignSystemTokensContractTest.php`、`tests/js/*.test.mjs`，以及 `tests/e2e/list-card-filter-parity.spec.js`（扁平列表）、`filter-grid-align.spec.js`（工具栏贴左）、`interaction-a11y.spec.js`（页面头、toast）。

## 提交信息

格式与用词规则取自 workbuddy2api-panel 的 172 条非 Merge 提交统计（feat 65 / fix 62 / chore 17 / ci 9），scope 按本仓目录名取。

### 格式

`type(scope): 描述`

- type 小写：常用 `feat`、`fix`、`chore`、`ci`；`refactor`、`docs`、`test`、`style`、`ui`、`revert` 少用
- scope 是改动所在目录：`lib`、`templates`、`l10n`、`tests`、`e2e`、`scripts`、`appinfo`、`docs`、`ci`。跨模块或全局改动不写 scope，别硬凑
- 描述用中文，标题显示宽度中位 57 列，不以句号结尾
- 破坏性改动在 type 后加 `!`：`feat(lib)!: 移除 v1 访问令牌——只认 v2` 

### 标题写什么

说清改了什么，不写「优化」「调整」「修复问题」这类看不出内容的词。

- `——` 引出原因或后果：`fix(templates): 工单页去掉重复的状态徽章——移动端换行`
- `（）` 标注来源：`（合入上游 90818dd）`、`（issue #12）`
- `+` 并列多件事：`fix(l10n): 补 zh_CN 词条 + 修正挪用语序`
- 移植或同步上游时把上游 hash / PR 号写进标题，不写空泛的「同步上游」

### 正文

多数提交带正文，写「为什么」和根因，不重复标题。

- 多件事用 `- ` 列点
- 硬换行，行宽中位 69 列
- 不用 `Co-Authored-By`，也不用 `BREAKING CHANGE` footer，破坏性改动用 `!`
- 与上游的合并提交保留原英文标题，公司二开的提交用中文，`git log` 里一眼能分开

### 反例

- `Merge pull request #44 from ...` —— 合并提交
- `修正券码兑换的提示文字` —— 没有 type
- `docs+fix: 快速开始重排 + 修 Docker bind mount 陷阱` —— 复合 type，拆成两条
- `fix: save config on Docker bind mounts` —— 本仓公司提交用中文，纯英文留给上游同步

## 版本发布

**发版 = 打 tag 推送，其余全自动。** release.yml 在 tag `v*` 推送时：校验 tag 形状 → 把 tag 号注入 `appinfo/info.xml` 的 `<version>` → l10n 门禁 → `composer install --no-dev` → 打 tar.gz（顶层目录 `maintenancecheck/`，口径与上游一致：排除 tests/e2e/scripts/.github/.git）→ 创建 GitHub Release 并上传包。

git tag 用上海时间，形如 `v2026.11.7.1500`：日期不补零，`1500` 是 15:00（时不补零、分两位、不带秒）。

```bash
VERSION=$(TZ=Asia/Shanghai date +%Y.%-m.%-d.%-H%M)
# TZ=Asia/Shanghai 在缺 tzdata 的环境（Windows git-bash）会静默回落 UTC，标签整整早 8 小时
[ "$(TZ=UTC date +%H)" = "$(TZ=Asia/Shanghai date +%H)" ] && VERSION=$(date -u -d '+8 hours' +%Y.%-m.%-d.%-H%M)
git tag "v$VERSION"
git push origin "v$VERSION"
```

两个版本号各管各的：

| 号 | 在哪 | 谁看 |
|---|---|---|
| `v2026.11.7.1500` | git tag / GitHub Release | 人，判断这批改动从什么时候上线 |
| 注入值 `2026.11.7.1500` | 发布包内 `appinfo/info.xml` 的 `<version>` | Nextcloud，靠它判断要不要升级 |

- **不要手改仓库里的 `<version>`**。仓库里留着上游基线值（1.3.5）即可，发布包内的版本由 release.yml 按 tag 注入，用 `version_compare` 比较，`2026.11.7.950` < `2026.11.7.1000`（数字比较，不补零也对）
- 上游 `v1.x` 形状的 tag 会被 workflow 的格式校验拒绝——防止误推上游 tag
- 打包产物只进 GitHub Release，不进版本库（见硬规则 1）
- 同一分钟再发才会撞号

## 修改后自检

```bash
composer validate --no-check-publish
find lib tests templates -name '*.php' -print0 | xargs -0 -n1 -P4 php -l   # 与 CI 一致
composer test:unit
```

CI 只跑语法检查（`php -l`），PHPUnit 需要 Nextcloud 引导，本地用 `composer test:docker`。
