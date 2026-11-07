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

## 修改后自检

```bash
composer validate --no-check-publish
find lib tests templates -name '*.php' -print0 | xargs -0 -n1 -P4 php -l   # 与 CI 一致
composer test:unit
```

CI 只跑语法检查（`php -l`），PHPUnit 需要 Nextcloud 引导，本地用 `composer test:docker`。
