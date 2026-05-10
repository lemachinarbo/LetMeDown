# Plan: Section Key Stability

## Phase 1: Test Scaffolding
- [ ] Create `tests/SectionTest.php` with necessary namespace and imports.
- [ ] Implement `setUpBeforeClass` to ensure `LetMeDown` is loaded.

## Phase 2: Implementation
- [ ] **Task 2.1**: Implement `test_named_section_key_is_correct`.
- [ ] **Task 2.2**: Implement `test_anonymous_section_key_is_numeric_index`.
- [ ] **Task 2.3**: Implement `test_section_key_is_stable_after_data_projection`.

## Phase 3: Verification
- [ ] Run `ddev composer test` and ensure all 111+ tests pass.
