# Testing

- The automated suite is Laravel Dusk browser tests ONLY, under tests/Browser; phpunit.xml points at tests/Browser. tests/Unit and tests/Feature were removed and must never be re-added. Confidence: 0.95
- Organizes browser tests under tests/Browser using either the role-first shape tests/Browser/{Role}/{Module}/Feature/<Name>Test.php or the feature-first shape tests/Browser/{Area}/[Feature/]<Name>Test.php. Confidence: 0.9
- Expects all test assertions to remain fully functional and pass after a test-suite reorganization. Confidence: 0.85
- Values adding test coverage for uncovered areas (e.g. API endpoints, multi-tenant isolation) alongside reorganization. Confidence: 0.6
- Expects every requirement/feature to be verified by its own dedicated, named Dusk browser test under tests/Browser (one test file per behaviour, e.g. tests/Browser/{Area}/{Name}Test.php), treating the test as part of the deliverable rather than an optional extra. Confidence: 0.8
