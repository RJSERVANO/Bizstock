# Asset provenance and usage

## New login illustration (2026-10-12)

public/assets/images/login-inventory-{480,960,1280}.webp are derivatives of NEW original artwork generated using the built-in OpenAI imagegen tool for this request. Source exec-b130248d-927f-4f97-bc95-df961992dc85.png in generation 01a12a28-e4ad-7b81-aeb1-15442f6df888; private workspace copy .tools/login-inventory-original.png. PHP GD re-encoding at quality 80 produces 13,784 / 32,476 / 45,810 byte local files. No external stock source/hotlink/third-party branding or attribution requirement was introduced. No text is baked into the artwork. Original prompt and usage are in [login design](login-design.md); [SHA-256 evidence](visual/login-design/asset-evidence.json). Homepage derivative hashes remain unchanged. Earlier login-reuse notes below are historical; current login no longer uses the homepage photo.

2026-10-12 login refinement reuses the same local stockroom derivatives below, with fictional-image disclosure and teal fallback. No new external image, hotlink or licensing assumption was added.

Public homepage assets reviewed 2026-10-12. No third-party stock photograph, externally found image or hotlinked asset is used. The supplied inspiration is a visual reference only; its branding, photos and UI screenshots were not copied into the website.

| Local asset | Source / license or usage basis | Attribution / disclosure |
|---|---|---|
| `public/assets/images/stockroom-{480,960,1440}.webp` | Original artwork generated specifically for this project using OpenAI imagegen, at the owner's request. Not an external stock license. Derivatives of the generated 1536×1024 PNG, resized/re-encoded locally using PHP GD at WebP quality 78. | The page identifies it as an AI-generated illustration with fictional people. It is not evidence of actual customers or employees. No third-party attribution applies to this generated source. |
| Shared outline icons / illustrative inventory preview | Original project SVG paths and semantic HTML/CSS; no external icon or dashboard-image asset imported. | Illustrative preview explicitly labels sample data; no installed records/photos appear. |
| Bootstrap vendor CSS/JS | Locked `twbs/bootstrap` 5.3.8 dependency; MIT license included in the Composer-installed package and vendor asset headers. Existing asset integrity checks retained. | Preserve upstream license notices when distributing. |

Generated source: `exec-fe8c9598-7d15-4f8a-b673-00e5d6c6cf1b.png`, retained privately as `.tools/homepage-stockroom-original.png` (outside public/, ignored by Git). Original SHA-256: `af5e5a478a90a5d0cb8ca887558244514134740192daeec694d77d68c92fd170`. The 1440px derivative SHA-256 is `b2fa40fd7ca6532085d92f0713c7166f324a39a2b56c30de1d1e1afa187f3902`.

Responsive image sizes: 480×320 / 20,062 bytes; 960×640 / 47,106 bytes; 1440×960 / 76,280 bytes. Native dimensions, responsive srcset/sizes, alt text and hero priority are specified. There are no raster images below the hero; screenshot galleries outside public/ lazy-load their captures. Private user/product uploads remain in their existing controlled storage, entirely separate from public artwork.

No external image license verification is claimed because no external stock image was selected. Future external additions require recorded source, verified commercial-use license, attribution and a locally optimized copy before use.
