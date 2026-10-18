# Campus Events API

Base URL: `http://127.0.0.1:8000/api/v1`

Every path below is relative to that base. Responses for `api/*` are JSON. Send `Accept: application/json`.

Authenticated requests send `Authorization: Bearer {token}`. A token is returned only by a successful registration or login. Logout deletes that current token.

Public registration always creates a student. `role`, `role_id`, and `role_slug` are rejected. One user has one role: `student`, `organizer`, or `administrator`.

List responses use Laravel pagination:

```json
{
  "data": [],
  "links": { "first": null, "last": null, "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 15, "total": 0 }
}
```

A single resource is wrapped in `data`, except the registration and login payloads, which place the user object in `data` beside `token`.

| Status | When it is used |
| --- | --- |
| 200 | Read, update, login, logout, or cancellation succeeded |
| 201 | A user, event, category, or registration was created |
| 204 | An event or category was deleted. The body is empty |
| 401 | Missing or invalid bearer token, or wrong login password |
| 403 | The token is valid but the role or ownership check failed |
| 404 | The record does not exist, or the user is not registered for that event |
| 409 | Duplicate registration, or a category still has events |
| 422 | Validation failed, the event is not open, the deadline has passed, the event is full, or the last administrator would be demoted |
| 500 | An unexpected server error |

`GET /events` and `GET /events/{event}` accept an optional bearer token. No token means a guest. A present but invalid token is 401. A valid token is that user, which is what makes draft visibility and `mine=1` work.

The Blade pages use `POST /browser-session` and `DELETE /browser-session`. Those are web routes, not part of `/api/v1`.

## POST /register

Creates a student account and returns a bearer token.

- Authentication: none
- Role: none
- Parameters: none
- Body: `name` (required string, max 255), `email` (required, unique), `password` (required, minimum 8, confirmed), `password_confirmation`. `role`, `role_id`, and `role_slug` are prohibited.
- Success: 201

```json
{
  "message": "Registration successful.",
  "token": "<issued-on-success>",
  "token_type": "Bearer",
  "data": {
    "id": 1,
    "name": "Postman Student",
    "email": "student@campus.test",
    "role": { "id": 1, "name": "Student", "slug": "student" },
    "created_at": "2026-10-18T00:00:00.000000Z"
  }
}
```

- Errors: 422 when fields are missing, the email is taken, the password is too short or not confirmed, or a role field is submitted. 429 after more than 10 requests from the same client in a minute.

## POST /login

Checks the email and password and returns a new bearer token.

- Authentication: none
- Role: none
- Parameters: none
- Body: `email` (required), `password` (required)
- Success: 200 with `message` "Login successful.", `token`, `token_type` "Bearer", and `data` in the same user shape as registration.
- Errors: 401 `{ "message": "The provided credentials are incorrect." }` when the email or password does not match. 422 when email or password is missing or the email is not a valid address. 429 after more than 10 requests in a minute. A failed login does not return a token.

## POST /logout

Deletes the bearer token used on this request.

- Authentication: required
- Role: any authenticated user
- Parameters: none
- Body: none
- Success: 200 `{ "message": "Logged out." }`
- Errors: 401 when no valid token is sent. 400 `{ "message": "This session cannot be revoked as a bearer token." }` when the user was authenticated without a personal access token.

## GET /users/me

Returns the user for the current token.

- Authentication: required
- Role: any authenticated user
- Parameters: none
- Body: none
- Success: 200 `{ "data": { "id", "name", "email", "role": { "id", "name", "slug" }, "created_at" } }`
- Errors: 401 when the token is missing, invalid, or already revoked. The password is never included.

## GET /users

Lists users, 15 per page, ordered by name. `per_page` is not accepted on this route.

- Authentication: required
- Role: administrator
- Parameters: `page` (optional)
- Body: none
- Success: 200 paginated `data` of user objects
- Errors: 401 unauthenticated. 403 `{ "message": "You do not have the required role." }` for a student or organizer.

## GET /users/{user}

Returns one user. `{user}` is a numeric id.

- Authentication: required
- Role: administrator
- Parameters: path `user`
- Body: none
- Success: 200 `{ "data": { user fields } }`
- Errors: 401 unauthenticated. 403 for a non-administrator. 404 when the id does not exist.

## PATCH /users/{user}/role

Changes the user's single role.

- Authentication: required
- Role: administrator
- Parameters: path `user`
- Body: `role` required, one of `student`, `organizer`, `administrator`. `role_id` is prohibited.
- Success: 200 `{ "data": { user fields, with the new role } }`
- Errors: 401 unauthenticated. 403 for a non-administrator. 404 when the user does not exist. 422 when `role` is missing or not one of the three slugs, or when the change would remove the last administrator: `{ "message": "The last administrator cannot be given another role." }`

## GET /events

Lists events the current viewer is allowed to see. Drafts are omitted for guests and students. An organizer also sees their own drafts. An administrator sees every status. Results are ordered by `event_date`, then `start_time`.

Upcoming means `published` and `event_date` is today or later. Past means `published` and `event_date` is before today. Cancelled events stay in the database and are left out of both timeframes.

- Authentication: optional bearer token
- Role: public. `mine=1` requires any authenticated user.
- Parameters:
  - `title` — partial title match
  - `organizer` — partial match on the organizer's name
  - `category` — category slug
  - `category_id` — category id
  - `date` — exact `event_date` (`Y-m-d`)
  - `timeframe` — `upcoming` or `past`
  - `mine` — boolean; `1` limits the list to events organized by the token user
  - `per_page` — 1 to 100, default 15
  - `page`
- Body: none
- Success: 200 paginated event objects. Each item has `id`, `title`, `description`, `venue`, `event_date`, `start_time`, `end_time`, `maximum_capacity`, `registration_deadline`, `status` (`draft`, `published`, or `cancelled`), `category` (`id`, `name`, `slug`), `organizer` (`id`, `name`), and `banner` (`id`, `path`, `url`, `original_name`) or `null`.
- Errors: 401 when a bearer token is present but invalid, or when `mine=1` is sent without a user. 422 when a query value is invalid, including a `timeframe` other than `upcoming` or `past`, a bad date, an unknown `category_id`, or `per_page` outside 1–100.

## GET /events/{event}

Returns one event. `{event}` is a numeric id.

- Authentication: optional bearer token
- Role: public for published and cancelled events. A draft is visible only to an administrator or the organizer who owns it.
- Parameters: path `event`
- Body: none
- Success: 200 `{ "data": { event fields } }`
- Errors: 401 for an invalid bearer token. 403 for a draft the caller cannot view. 404 when the id does not exist.

## POST /events

Creates an event. The organizer is the authenticated user. An administrator may instead send `organizer_id` for a user whose role is organizer or administrator.

- Authentication: required
- Role: organizer or administrator
- Parameters: none
- Body (JSON, or multipart when a banner file is included):
  - `title` required, max 255
  - `description` required, max 5000
  - `category_id` required, existing category
  - `venue` required, max 255
  - `event_date` required date
  - `start_time` and `end_time` required `H:i:s`, and the end must be after the start
  - `maximum_capacity` required integer, minimum 1
  - `registration_deadline` required date, on or before the event start
  - `status` required: `draft`, `published`, or `cancelled`
  - `organizer_id` prohibited unless the caller is an administrator
  - `banner` optional file: jpg, jpeg, png, webp, or gif, maximum 2048 KB
  - `banner_path` optional existing public path matching `events/banners/{generated-name}`. It cannot be sent together with `banner`.
- Success: 201 `{ "data": { event fields } }`
- Errors: 401 unauthenticated. 403 for a student, or for any other caller who cannot create events. 422 for missing or invalid fields, an end time that is not after the start, or a deadline after the event start.

## PUT /events/{event}

Replaces an event. The same fields as create are required. Omitted required fields fail validation. A non-administrator cannot change `organizer_id`.

- Authentication: required
- Role: administrator, or the organizer who owns the event
- Parameters: path `event`
- Body: same fields as `POST /events`
- Success: 200 `{ "data": { event fields } }`
- Errors: 401 unauthenticated. 403 when the caller does not own the event and is not an administrator. 404 when the event does not exist. 422 for invalid fields.

## PATCH /events/{event}

Updates only the fields that are sent. Schedule rules still apply to the combined new and existing values.

- Authentication: required
- Role: administrator, or the organizer who owns the event
- Parameters: path `event`
- Body: any subset of the create fields. A field that is present cannot be empty when the create rule says it is required. `slug` is not an event field.
- Success: 200 `{ "data": { event fields } }`
- Errors: 401, 403, 404, and 422 as for replace.

## DELETE /events/{event}

Deletes the event. Registrations and images that belong to it are removed with it.

- Authentication: required
- Role: administrator, or the organizer who owns the event
- Parameters: path `event`
- Body: none
- Success: 204 with an empty body
- Errors: 401 unauthenticated. 403 when the caller cannot delete it. 404 when the event does not exist.

## GET /categories

Lists categories ordered by name.

- Authentication: none
- Role: none
- Parameters: `per_page` (1–100, default 15), `page`
- Body: none
- Success: 200 paginated category objects: `id`, `name`, `slug`, `description`
- Errors: 422 when `per_page` is outside 1–100.

## GET /categories/{category}

Returns one category. `{category}` is a numeric id.

- Authentication: none
- Role: none
- Parameters: path `category`
- Body: none
- Success: 200 `{ "data": { "id", "name", "slug", "description" } }`
- Errors: 404 when the id does not exist.

## POST /categories

Creates a category. The slug is generated from the name. A submitted slug is rejected.

- Authentication: required
- Role: administrator
- Parameters: none
- Body: `name` required, unique, max 255. `description` optional, max 255. `slug` prohibited.
- Success: 201 `{ "data": { "id", "name", "slug", "description" } }`
- Errors: 401 unauthenticated. 403 for a student or organizer. 422 when the name is missing or already used, or when `slug` is submitted.

## PUT /categories/{category}

Replaces a category. `name` is required. The slug is regenerated from the name.

- Authentication: required
- Role: administrator
- Parameters: path `category`
- Body: `name` required, unique except for this category, max 255. `description` optional, max 255. `slug` prohibited.
- Success: 200 `{ "data": { category fields } }`
- Errors: 401, 403, 404, and 422 for a missing name, a duplicate name, or a submitted slug.

## PATCH /categories/{category}

Updates the name, the description, or both. A new name regenerates the slug.

- Authentication: required
- Role: administrator
- Parameters: path `category`
- Body: `name` optional but required when present. `description` optional. `slug` prohibited.
- Success: 200 `{ "data": { category fields } }`
- Errors: 401, 403, 404, and 422 as for replace, except that a missing name is allowed when the field is omitted.

## DELETE /categories/{category}

Deletes a category that is not used by any event.

- Authentication: required
- Role: administrator
- Parameters: path `category`
- Body: none
- Success: 204 with an empty body
- Errors: 401 unauthenticated. 403 for a non-administrator. 404 when the category does not exist. 409 `{ "message": "This category cannot be deleted while events still use it." }`

## GET /users/me/registrations

Lists the current user's registrations, newest first, 15 per page. `per_page` is not accepted on this route.

- Authentication: required
- Role: any authenticated user. The list contains only that user's rows.
- Parameters: `page`
- Body: none
- Success: 200 paginated rows:

```json
{
  "data": [
    {
      "id": 1,
      "event_id": 4,
      "user_id": 2,
      "registered_at": "2026-10-18T00:00:00.000000Z",
      "event": {
        "id": 4,
        "title": "Postman Seminar",
        "venue": "Main Hall",
        "event_date": "2027-06-15",
        "status": "published"
      }
    }
  ]
}
```

- Errors: 401 unauthenticated.

## POST /events/{event}/registrations

Registers the current user for one event. Any authenticated user may register. The event row is locked for the capacity check, and the unique `(event_id, user_id)` pair stops a duplicate.

Registration is accepted only when the event is `published`, the deadline has not passed, the user is not already registered, and the registration count is below `maximum_capacity`.

- Authentication: required
- Role: any authenticated user
- Parameters: path `event`
- Body: none
- Success: 201

```json
{
  "message": "Registration successful.",
  "data": {
    "id": 1,
    "event_id": 4,
    "user_id": 2,
    "registered_at": "2026-10-18T00:00:00.000000Z",
    "event": {
      "id": 4,
      "title": "Postman Seminar",
      "venue": "Main Hall",
      "event_date": "2027-06-15",
      "status": "published"
    }
  }
}
```

- Errors:
  - 401 unauthenticated
  - 404 when the event does not exist
  - 409 `{ "message": "This user is already registered for the event.", "reason": "already_registered" }`
  - 422 `{ "reason": "not_open" }` when the status is not `published`
  - 422 `{ "reason": "deadline_passed" }` when the deadline is in the past
  - 422 `{ "reason": "at_capacity" }` when the event is full

## DELETE /events/{event}/registrations

Cancels the current user's registration for that event.

- Authentication: required
- Role: the user who owns the registration
- Parameters: path `event`
- Body: none
- Success: 200 `{ "message": "Registration cancelled." }`
- Errors: 401 unauthenticated. 404 `{ "message": "You are not registered for this event.", "reason": "not_registered" }` when this user has no registration, or when the event does not exist.
