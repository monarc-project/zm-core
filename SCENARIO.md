# Scenario bounded domain

SRA-01 establishes the Scenario API foundation at `/api/scenario/v1`. Its
canonical OpenAPI source is [`openapi/scenario-v1.yaml`](openapi/scenario-v1.yaml).

| Repository | Responsibility |
| --- | --- |
| `zm-core` | Shared Scenario value contracts, feature adapter, readiness helper, OpenAPI contract, and `monarc_common` Scenario Library schema/table access. |
| `zm-client` | FrontOffice/client-database Scenario work and its API routes. |
| `zm-backoffice` | BackOffice event/template catalog authoring API routes and role checks. |
| `MonarcAppFO`, `MonarcAppBO` | Host configuration and deployment registration. |

The foundation is disabled by default through `scenario.enabled`; later,
authorized capability routes must enforce that setting as well as MONARC's
existing client and role permissions; FrontOffice analysis routes also enforce
their ANR permissions. The public probe routes expose no
tenant, database, credential, or assessment information. `/health` is process
liveness; `/ready` tests the host's existing Doctrine dependency.

Local checks: run `composer test` in `zm-core`, `composer run phpcs` in
`zm-client` and `zm-backoffice`, validate the OpenAPI document, then load each
host application with `scenario.enabled=false`. Shared Scenario Library schema
changes are additive Phinx migrations in `zm-core` and run against
`monarc_common`; client-specific assessment schema remains in `zm-client` and
runs against the relevant `monarc_cli` database. No separate package migration
discovery and no bootstrap SQL changes are allowed.

## SRA-03 legacy authentication interface

The current release is locked to the server-side `scenario.identity.mode:
legacy` default in both hosts. It makes no external identity request. The
versioned contract additionally exposes these internal bridge operations:

1. The authenticated same-origin MONARC launcher posts its existing token only
   to `POST /api/scenario/v1/auth/bridge/issue`, with a nonce and safe relative
   return path. FrontOffice includes an ANR and verifies user access; BackOffice
   is catalogue-wide and does not accept an ANR. It receives a five-minute
   one-time code.
2. `scenario-ui` sends that code, nonce and its secret-manager-provided service
   credential to the internal-only `POST /auth/bridge/consume` endpoint. The
   response contains an opaque Scenario session intended only for the BFF
   cookie.
3. On every protected request the BFF calls `POST /auth/session/resolve`; it
   calls `POST /auth/session/revoke` at Scenario logout. The server rechecks
   the active MONARC token and user status, plus ANR membership for FrontOffice,
   and revokes a stale session on the same request. A successful resolve
   refreshes that same source token using the normal MONARC TTL. The opaque
   Scenario session uses that configured TTL as a bounded hard lifetime and
   never extends it.

The bridge stores digests of one-time codes, opaque sessions and the source
legacy-token binding, never raw values. Service credentials are supplied only
by the approved secret manager. SRA-04 mounts the BFF under the same origin
at `/scenario` and ensures the consume/resolve/revoke endpoints are not public
services; it must preserve the BFF's CSRF, strict-origin and `no-store` rules.
