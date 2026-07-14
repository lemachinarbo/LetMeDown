# Changelog

## [1.9.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.8.0...v1.9.0) (2026-07-14)


### Features

* add getcleanmarkdown method with resolvers ([4a253b4](https://github.com/lemachinarbo/LetMeDown/commit/4a253b4c333a599eea5d8dbea927d3449ae13113))

## [1.8.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.7.1...v1.8.0) (2026-07-13)


### Features

* add cleanmarkdown support to content and sections ([e1748e8](https://github.com/lemachinarbo/LetMeDown/commit/e1748e8eb55357cbc4643b498425f3334f2e6c29))

## [1.7.1](https://github.com/lemachinarbo/LetMeDown/compare/v1.7.0...v1.7.1) (2026-05-31)


### Bug Fixes

* accept compact field markers ([141954e](https://github.com/lemachinarbo/LetMeDown/commit/141954e5d8c54e93db52269df4b923ff9c98ed22))
* centralize href sanitization and harden link projection ([7972fff](https://github.com/lemachinarbo/LetMeDown/commit/7972fff6844e3de78eb96fb2c6f5b0db81f10ccf))
* correct Setext heading parsing edge cases for multi-line, blockquotes, bullets, and underlines ([7d3ebb9](https://github.com/lemachinarbo/LetMeDown/commit/7d3ebb9137170d2a8807107fe05cf6fca132edf4))
* enforce commonmark info string rules in fence detection ([b04d46e](https://github.com/lemachinarbo/LetMeDown/commit/b04d46e917da4a62fac10e6caef9fc3dceca20df))
* expand block link security test to cover bypass vectors ([5e281a0](https://github.com/lemachinarbo/LetMeDown/commit/5e281a0cd7aa3ecfca8ba548e1e7f448a35d4c52))
* ignore fenced code markers ([9ebfaa1](https://github.com/lemachinarbo/LetMeDown/commit/9ebfaa12f7575e8b756074aa02ebccaf5836b9e4))
* ignore fenced heading lines ([ca03812](https://github.com/lemachinarbo/LetMeDown/commit/ca03812ab53084b977d291880a47c4c0388f1d3c))
* implement marker stripping on field slicing and support adjacent openers ([78027d7](https://github.com/lemachinarbo/LetMeDown/commit/78027d70812b0c12a2261d8160460f53e416511b))
* optimize duplicate subsection parsing performance ([0c343f0](https://github.com/lemachinarbo/LetMeDown/commit/0c343f0c9305f7bb5f5c82c517ba59c8bea66254))
* preserve first duplicate subsection ([5ab21a3](https://github.com/lemachinarbo/LetMeDown/commit/5ab21a3494ab2a9baec0ea53738bba98866806e5))
* preserve setext block headings ([bbdea1e](https://github.com/lemachinarbo/LetMeDown/commit/bbdea1e56b2a98988d6399faae9de4a01a6bf069))
* reject malformed field overlap ([217b643](https://github.com/lemachinarbo/LetMeDown/commit/217b6438bbb554125e3fbd6c8864a9c22bd36e23))
* reserve magic properties on ContentData and Block and implement __isset on Block ([bf6d532](https://github.com/lemachinarbo/LetMeDown/commit/bf6d5321159325b9e4076cc196b7c6927261dcc0))
* reserve section magic properties ([664001a](https://github.com/lemachinarbo/LetMeDown/commit/664001aecf30e872cc28a10a7d07abc5aa333241))
* resolve potential redos in comment regex and de-chain test assertion ([939700c](https://github.com/lemachinarbo/LetMeDown/commit/939700c6a9a6c39ecbe227410dd4097a224f5a3b))
* restore global libxml internal errors state and clear errors ([993a3b2](https://github.com/lemachinarbo/LetMeDown/commit/993a3b290c92903338058887fcb9086de280f69e))
* sanitize block link hrefs ([5c7fcc6](https://github.com/lemachinarbo/LetMeDown/commit/5c7fcc6e2faf477c28eba0a7c2c0673c02969800))
* sanitize list item links and images; correct Setext heading offset ([d346d91](https://github.com/lemachinarbo/LetMeDown/commit/d346d9119458f73e90cb0c97e10c5f7bfcd2bc88))
* sanitize projected link hrefs ([a225994](https://github.com/lemachinarbo/LetMeDown/commit/a225994493bbc244fe3e5e8c7fcfd08e4614e2b3))
* support binding emphasis variants ([b35fb1c](https://github.com/lemachinarbo/LetMeDown/commit/b35fb1c13e51d6adcbaccb55d7264d5b6701551c))


### Miscellaneous Chores

* mark 1bd72ec review done in tracks ledger ([b7dcf88](https://github.com/lemachinarbo/LetMeDown/commit/b7dcf88ced02261fd8cea38026f6cfed350e4449))
* mark 7aa060f review done in tracks ledger ([6f6ca02](https://github.com/lemachinarbo/LetMeDown/commit/6f6ca029cb6d6957e32a4a286abf96b1a028fa27))
* mark 92bca59 review done in tracks ledger ([d62fb1f](https://github.com/lemachinarbo/LetMeDown/commit/d62fb1ff5f3f2cc6f4e4bee74e8a66acbbe581bd))
* mark 9c0754f review done in tracks ledger ([f6145e4](https://github.com/lemachinarbo/LetMeDown/commit/f6145e486bddf482ed6ea2adf929450ae8451059))
* mark b2e33c1 review done in tracks ledger ([ba6407d](https://github.com/lemachinarbo/LetMeDown/commit/ba6407d5c0ba0fdb85b8c3292c8a260ec308a613))
* mark b7cd13d review done in tracks ledger ([6a0c773](https://github.com/lemachinarbo/LetMeDown/commit/6a0c773ede75e7cad20c8edef6ac91e0f9fba04f))
* mark be7764a review done in tracks ledger ([212f772](https://github.com/lemachinarbo/LetMeDown/commit/212f772b77013a60f59aa130d70c062f08062a64))
* mark c226e4f review done in tracks ledger ([e864963](https://github.com/lemachinarbo/LetMeDown/commit/e864963ed5d5820ad985c183c53187d054ce1bde))
* mark ce2d523 review done in tracks ledger ([cf93cde](https://github.com/lemachinarbo/LetMeDown/commit/cf93cde58cd043faade7afdcef99ae91c36cb58b))
* mark efdf284 review done in tracks ledger ([6c60a75](https://github.com/lemachinarbo/LetMeDown/commit/6c60a757a1aa54c38ffbdf9ed211035c6b7c9345))
* register commit review series in tracks ledger ([3387399](https://github.com/lemachinarbo/LetMeDown/commit/3387399eaf89d1db7a6f449483bb9e8e4891737b))

## [1.7.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.6.0...v1.7.0) (2026-05-28)


### Features

* add tag property to ContentElement to track HTML element names ([fb4b9d8](https://github.com/lemachinarbo/LetMeDown/commit/fb4b9d8b1be32fa33ac5704c05586504d4cd280b))


### Miscellaneous Chores

* clarify autonomous commit exception ([b31b142](https://github.com/lemachinarbo/LetMeDown/commit/b31b142c7a2a41419d1ff11216fb903805e86b69))

## [1.6.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.5.0...v1.6.0) (2026-05-17)


### Features

* add recursive block projection and section 0 mapping ([fa3ca17](https://github.com/lemachinarbo/LetMeDown/commit/fa3ca17be430da0f0c31e0596a5ff1d3b7dc1e1f))
* implement __isset in Section and ContentData ([505916c](https://github.com/lemachinarbo/LetMeDown/commit/505916cf1fadb540a9e9b9bff2c1f4fa3336e6bb))


### Bug Fixes

* add LIBXML_NONET to DOMDocument HTML parsing ([f8a803a](https://github.com/lemachinarbo/LetMeDown/commit/f8a803a4a9fde6c85ece79f578513ff95e7100a3))


### Miscellaneous Chores

* lock active tracks for code health and performance optimizations ([f977927](https://github.com/lemachinarbo/LetMeDown/commit/f977927938e3103443df36a2309988e624884f2c))
* lock track for security-libxml-nonet session ([93eafec](https://github.com/lemachinarbo/LetMeDown/commit/93eafecf5c4e825544c5c9300b6df325c3e533b9))
* lock track for security-libxml-nonet-729-862 session ([0b0d8f9](https://github.com/lemachinarbo/LetMeDown/commit/0b0d8f9499fa7a72620ad7f11cfd1fba6c7f5991))
* lock tracks for performance, testing, and code health sessions ([a63c1f2](https://github.com/lemachinarbo/LetMeDown/commit/a63c1f2137e15f6956fc2b8f8b59bdffade49090))
* update composer.lock dependencies and content hash ([3ef96a3](https://github.com/lemachinarbo/LetMeDown/commit/3ef96a3fa6edeea13f4b3dbca2652649cab71e5c))

## [1.5.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.4.0...v1.5.0) (2026-05-10)


### Features

* add correct index key mapping for parsed sections ([#48](https://github.com/lemachinarbo/LetMeDown/issues/48)) ([7aee157](https://github.com/lemachinarbo/LetMeDown/commit/7aee1579ab69bb0cafe8d4bad0e86bf03771ee25))
* add path traversal security tests ([#51](https://github.com/lemachinarbo/LetMeDown/issues/51)) ([04e56ab](https://github.com/lemachinarbo/LetMeDown/commit/04e56abe7031f7fb3ff2851399994b6d2efed1ba))
* add test suite coverage for field bindings ([#49](https://github.com/lemachinarbo/LetMeDown/issues/49)) ([13c0377](https://github.com/lemachinarbo/LetMeDown/commit/13c0377dc87412cfea2730c794fc3d754d92f97b))


### Bug Fixes

* upgrade erusev/parsedown to 1.8.0 to resolve redos vulnerability ([9327e19](https://github.com/lemachinarbo/LetMeDown/commit/9327e190f53b8a0985120673c2ab3063d729a075))


### Performance Improvements

* optimize DOMDocument parsing and node serialization ([a46426d](https://github.com/lemachinarbo/LetMeDown/commit/a46426d7f1720cc9574fd59f1a55a0354c63674e))
* optimize image scheme extraction for large URLs ([9c482fb](https://github.com/lemachinarbo/LetMeDown/commit/9c482fb8e427472ca9f325951c5ee14f782cab36))
* optimize node serialization by reusing ownerDocument ([fe2c964](https://github.com/lemachinarbo/LetMeDown/commit/fe2c964f3585f9f1878cd0f4ab28332b22c32cf8))
* replace preg_replace with str_replace in serializeNode ([#52](https://github.com/lemachinarbo/LetMeDown/issues/52)) ([e2e5613](https://github.com/lemachinarbo/LetMeDown/commit/e2e5613b83b76c3b0cb445d77f00b93a124aac72))


### Miscellaneous Chores

* deep cleanup of legacy scratch files and local agent state ([70d0340](https://github.com/lemachinarbo/LetMeDown/commit/70d034088bd8e7fa87ca7892af9259dc6d6b6039))
* finalize audit track and update conductor protocol ([bc53f57](https://github.com/lemachinarbo/LetMeDown/commit/bc53f572df0382bc99b76a92aa015de4cc823b99))
* finalize conductor skill and add section key tests ([df12b20](https://github.com/lemachinarbo/LetMeDown/commit/df12b209d129bc5781dbb6a37646d7da9ddc1598))
* finalize security-path-traversal track ([cc02c62](https://github.com/lemachinarbo/LetMeDown/commit/cc02c6288c94c01a1cfe4b11feba66ea445b1f0a))
* ignore conductor watcher files ([6af9207](https://github.com/lemachinarbo/LetMeDown/commit/6af9207d4a2715576955b6142c4c29e36273d904))
* ignore conductor-jules scratch directory ([96d0080](https://github.com/lemachinarbo/LetMeDown/commit/96d0080f34cf10f4ebf71c60f95ecbb668ac281e))
* implement Scratch Discipline to prevent root clutter ([f5ef1f4](https://github.com/lemachinarbo/LetMeDown/commit/f5ef1f4b82833478787585bbbf0551416ed427b5))
* initialize conductor-jules and start section-key track ([ab1b897](https://github.com/lemachinarbo/LetMeDown/commit/ab1b897bf238a6e34643578e1ce69ee2da476bc4))
* initiate tests-projection-shapes track ([a00b148](https://github.com/lemachinarbo/LetMeDown/commit/a00b148852a4d6dbda1f3a4b9cc09f4cb7ad28e4))
* refine conductor protocol and initiate perf-dom-audit ([f1b07aa](https://github.com/lemachinarbo/LetMeDown/commit/f1b07aa62b19024603f74d12e74211ecb0a33525))
* refine conductor protocol to favor direct ingestion over PRs ([27a4035](https://github.com/lemachinarbo/LetMeDown/commit/27a4035ed7a8813ab7b386003ee4c00f12622c30))
* restore conductor protocol to agents.md ([2b497b2](https://github.com/lemachinarbo/LetMeDown/commit/2b497b2276c339acad3c3c14f95b582f5006e682))
* start multi-section track ([f5f14fe](https://github.com/lemachinarbo/LetMeDown/commit/f5f14fe03bb87a563770d696e007fc4b942863ec))
* start test-suite-audit track ([a84bc37](https://github.com/lemachinarbo/LetMeDown/commit/a84bc37e4bb19688c8c981d9d6d229a7c71ff04a))

## [1.4.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.3.2...v1.4.0) (2026-05-08)


### Features

* add test for getrawdocument with null frontmatter ([ff96cc6](https://github.com/lemachinarbo/LetMeDown/commit/ff96cc6e8435022a80c42730d3d5f89f6acf31f4))
* add tests for bom handling in markdown ([5da797e](https://github.com/lemachinarbo/LetMeDown/commit/5da797eecba3cf8ac4c134c9fefb09cab43701bd))


### Bug Fixes

* prevent xss via link protocol bypass when parse_url fails ([aa875a4](https://github.com/lemachinarbo/LetMeDown/commit/aa875a4d2c3016c4a5bd649433e6ef4fcd5766c5))
* sanitize image urls to prevent xss ([c650bde](https://github.com/lemachinarbo/LetMeDown/commit/c650bdeb50cd583105ab48d6d427c0a72a237d2f))


### Performance Improvements

* cache findAllMarkers results to prevent redundant regex searches ([62ded45](https://github.com/lemachinarbo/LetMeDown/commit/62ded452b7a8752f561ebc77441f0d2e41297705))


### Miscellaneous Chores

* remove unused method collectionToArray ([e72bd5f](https://github.com/lemachinarbo/LetMeDown/commit/e72bd5fdd297673678bb8b4248d72b31a5992a73))

## [1.3.2](https://github.com/lemachinarbo/LetMeDown/compare/v1.3.1...v1.3.2) (2026-04-28)


### Bug Fixes

* force release for docs update ([b1a9a79](https://github.com/lemachinarbo/LetMeDown/commit/b1a9a797d5b01f6ffdb6bc3ff5de4c8ee6f84ba0))

## [1.3.1](https://github.com/lemachinarbo/LetMeDown/compare/v1.3.0...v1.3.1) (2026-04-28)


### Bug Fixes

* add raw html parser toggle ([ca3fe5f](https://github.com/lemachinarbo/LetMeDown/commit/ca3fe5f89c4a4ed02c2886dce109113792896148))
* disable Parsedown SafeMode to allow raw HTML in content fields ([8858728](https://github.com/lemachinarbo/LetMeDown/commit/88587282d1a31f5ea21df40672bb7f05f7a9389b))

## [1.3.0](https://github.com/lemachinarbo/LetMeDown/compare/v1.2.3...v1.3.0) (2026-04-19)


### Features

* integrate release-please for automated versioning and release management ([341bea5](https://github.com/lemachinarbo/LetMeDown/commit/341bea552127dacd3893c50efaa4ac6626109971))


### Bug Fixes

* prevent path traversal in markdown loader ([7be153f](https://github.com/lemachinarbo/LetMeDown/commit/7be153fd1d72497801e70732e4b501813fbf8c26))
* prevent phar deserialization vulnerability in load() ([f2bfc09](https://github.com/lemachinarbo/LetMeDown/commit/f2bfc09e38279f8ceb2c755ed44acbc3d1c6412a))
* remove redundant id from release-please job in release workflow ([5691bb4](https://github.com/lemachinarbo/LetMeDown/commit/5691bb47b167343065277da7a95a6b56718f26bc))
* sanitize unsafe url schemes in link fields to prevent xss ([90c03c6](https://github.com/lemachinarbo/LetMeDown/commit/90c03c629c8fe0384fa4e45fd0a72c1f9d4aea0b))
* update release configuration ([478650e](https://github.com/lemachinarbo/LetMeDown/commit/478650e9c919829982e641b98ccd3f70e6e9d9dc))


### Miscellaneous Chores

* add agents configuration file ([a99ed2b](https://github.com/lemachinarbo/LetMeDown/commit/a99ed2b9bacbf84536b35c5f6a45380de400f5f8))

## v1.2.3
- added `ContentData::data()` for top-level named section projection
- made `data()` recursive and associative across sections, subsections, field containers, and fields
- field containers now serialize as structural nodes with named children and ordered `items`
- iterable fields expose `items`, while scalar fields keep associative field payloads without positional array flattening
- section-like nodes now expose `subsections` instead of legacy subsection `items`
- tightened sparse output so empty string values are omitted from public `data()` payloads

## v1.2.2
- fixed `data()` projection for structured fields so list and image payloads keep predictable named keys

## v1.2.1
- fixed hyphenated section and subsection markers like `<!-- section:feature-grid -->` and `<!-- sub:name-of-section -->`
- made marker name matching consistent across sections, subsections, and fields

## v1.2

- fixed Composer autoload so LetMeDown classes load correctly from one-file source
- added a plain `data()` view for content structure of sections and fields
