# Changelog

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
