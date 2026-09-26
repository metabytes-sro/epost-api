# Contributing

Thanks for taking the time to contribute. Bug reports, feature requests and
pull requests are all welcome.

## Getting started

```bash
git clone https://github.com/metabytes-sro/epost-api.git
cd epost-api
composer install
```

The full check that CI runs is available as one command:

```bash
composer check
```

It runs, in order:

| Command             | What it does                                                |
|---------------------|-------------------------------------------------------------|
| `composer lint`     | Checks code style with PHP-CS-Fixer (`composer fix` applies it) |
| `composer analyse`  | Runs PHPStan at the highest level                           |
| `composer test`     | Runs the PHPUnit suite                                      |

Coverage reports need the `pcov` or `xdebug` extension:

```bash
composer test:coverage
```

## Pull requests

- Open the pull request against `master` (2.x). Fixes for the 1.x line go against the `1.x` branch.
- Keep changes focused. Unrelated refactoring belongs in its own pull request.
- Every change in behaviour needs a test. The suite runs against a mocked HTTP
  client, so no E-POST credentials are required.
- Add a line to the `Unreleased` section of `CHANGELOG.md`.
- Public API changes that break backwards compatibility need a note in
  `UPGRADE.md`. They are only merged for a new major version.
- Make sure `composer check` passes before you push. CI runs the same checks on
  every supported PHP version.

## Coding standard

The code follows [PER Coding Style 2.0](https://www.php-fig.org/per/coding-style/)
with the additional rules configured in `.php-cs-fixer.dist.php`. Run
`composer fix` to format your changes automatically.

## Reference material

The E-POSTBUSINESS API specification the package is built against is kept in
[`docs/api/`](docs/api/README.md). When the API changes, update the files there
first so the diff shows what the code has to follow.
