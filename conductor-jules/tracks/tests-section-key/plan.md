# Plan: Section Key Stability

## Phase 1: Test Scaffolding
- [x] Create `tests/SectionTest.php` with necessary namespace and imports.
- [x] Implement `setUpBeforeClass` to ensure `LetMeDown` is loaded.

## Phase 2: Implementation
- [x] **Task 2.1**: Implement `test_named_section_key_is_correct`.
- [x] **Task 2.2**: Implement `test_anonymous_section_key_is_numeric_index`.
- [x] **Task 2.3**: Implement `test_section_key_is_stable_after_data_projection`.

## Phase 3: Verification
- [x] Run `ddev composer test` and ensure all 111+ tests pass.
