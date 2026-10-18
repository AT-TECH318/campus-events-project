const fs = require("fs");
const path = require("path");

function script(lines) {
  return { type: "text/javascript", exec: lines };
}

function events(testLines, preLines) {
  const list = [];
  if (preLines) {
    list.push({ listen: "prerequest", script: script(preLines) });
  }
  if (testLines) {
    list.push({ listen: "test", script: script(testLines) });
  }
  return list;
}

function useToken(name) {
  return [
    `const saved = pm.collectionVariables.get("${name}");`,
    "if (saved) {",
    "    pm.collectionVariables.set(\"token\", saved);",
    "}",
  ];
}

function statusLines(code) {
  return [
    `pm.test("status is ${code}", function () {`,
    `    pm.response.to.have.status(${code});`,
    "});",
  ];
}

function example(name, code, status, body) {
  return [{
    name,
    status,
    code,
    _postman_previewlanguage: "json",
    header: [{ key: "Content-Type", value: "application/json" }],
    body,
  }];
}

function item(name, method, urlPath, { body, auth = true, test, pre, description, sample } = {}) {
  const headers = [{ key: "Accept", value: "application/json" }];
  const request = {
    method,
    header: headers,
    url: "{{baseUrl}}" + urlPath,
    description: description || name,
    auth: auth
      ? { type: "bearer", bearer: [{ key: "token", value: "{{token}}", type: "string" }] }
      : { type: "noauth" },
  };
  if (body !== undefined) {
    headers.push({ key: "Content-Type", value: "application/json" });
    request.body = {
      mode: "raw",
      raw: body,
      options: { raw: { language: "json" } },
    };
  }
  const result = { name, request, event: events(test, pre) };
  if (sample) {
    result.response = example(sample.name, sample.code, sample.status, sample.body);
  }
  return result;
}

const eventBody = `{
  "title": "{{eventTitle}}",
  "description": "A seminar created by the Postman collection.",
  "category_id": {{categoryId}},
  "venue": "Main Hall",
  "event_date": "{{futureDate}}",
  "start_time": "09:00:00",
  "end_time": "12:00:00",
  "maximum_capacity": 25,
  "registration_deadline": "{{futureDeadline}}",
  "status": "published"
}`;

const collection = {
  info: {
    name: "Campus Events API – WEB303 Assignment 2",
    description: "Versioned Campus Events API. Set demoPassword locally before running. baseUrl starts at http://127.0.0.1:8000/api/v1. The token variable is filled only by a successful login or registration response and is not stored in this file.",
    schema: "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
  },
  variable: [
    { key: "baseUrl", value: "http://127.0.0.1:8000/api/v1" },
    { key: "token", value: "" },
    { key: "demoPassword", value: "" },
    { key: "organizerEmail", value: "organizer@campus.test" },
    { key: "adminEmail", value: "admin@campus.test" },
    { key: "futureDate", value: "2027-06-15" },
    { key: "futureDeadline", value: "2027-06-14 17:00:00" },
    { key: "pastDate", value: "2020-01-15" },
    { key: "pastDeadline", value: "2020-01-14 17:00:00" },
    { key: "closedDate", value: "2027-08-01" },
    { key: "closedDeadline", value: "2020-01-02 17:00:00" },
    { key: "runStamp", value: "" },
    { key: "newUserEmail", value: "" },
    { key: "secondUserEmail", value: "" },
    { key: "studentToken", value: "" },
    { key: "secondStudentToken", value: "" },
    { key: "organizerToken", value: "" },
    { key: "adminToken", value: "" },
    { key: "newUserId", value: "" },
    { key: "organizerName", value: "" },
    { key: "categoryId", value: "" },
    { key: "categorySlug", value: "" },
    { key: "eventId", value: "" },
    { key: "eventTitle", value: "" },
    { key: "pastEventId", value: "" },
    { key: "pastTitle", value: "" },
    { key: "draftEventId", value: "" },
    { key: "draftTitle", value: "" },
    { key: "capacityEventId", value: "" },
    { key: "managedCategoryId", value: "" },
    { key: "blockerEventId", value: "" },
  ],
  event: [{
    listen: "prerequest",
    script: script([
      "if (!pm.collectionVariables.get('runStamp')) {",
      "    const stamp = Date.now();",
      "    pm.collectionVariables.set('runStamp', String(stamp));",
      "    pm.collectionVariables.set('newUserEmail', 'postman.' + stamp + '@campus.test');",
      "    pm.collectionVariables.set('secondUserEmail', 'postman.second.' + stamp + '@campus.test');",
      "    pm.collectionVariables.set('eventTitle', 'Postman Seminar ' + stamp);",
      "    pm.collectionVariables.set('pastTitle', 'Postman Past ' + stamp);",
      "    pm.collectionVariables.set('draftTitle', 'Postman Draft ' + stamp);",
      "    pm.collectionVariables.set('categoryName', 'Postman Category ' + stamp);",
      "}",
    ]),
  }],
  item: [
    {
      name: "Authentication",
      item: [
        item("Register a student", "POST", "/register", {
          auth: false,
          body: `{
  "name": "Postman Student",
  "email": "{{newUserEmail}}",
  "password": "{{demoPassword}}",
  "password_confirmation": "{{demoPassword}}"
}`,
          description: "Public registration. The API assigns the student role.",
          sample: {
            name: "201 Created",
            code: 201,
            status: "Created",
            body: "{\n  \"message\": \"Registration successful.\",\n  \"token\": \"<issued-on-success>\",\n  \"token_type\": \"Bearer\",\n  \"data\": {\n    \"id\": 1,\n    \"name\": \"Postman Student\",\n    \"email\": \"postman.student@campus.test\",\n    \"role\": { \"id\": 1, \"name\": \"Student\", \"slug\": \"student\" }\n  }\n}",
          },
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('registration returns a student profile and bearer token', function () {",
            "    pm.expect(body.token_type).to.eql('Bearer');",
            "    pm.expect(body.token).to.be.a('string').and.not.empty;",
            "    pm.expect(body.data.email).to.eql(pm.collectionVariables.get('newUserEmail'));",
            "    pm.expect(body.data.role.slug).to.eql('student');",
            "    pm.expect(body.data.password).to.be.undefined;",
            "});",
            "if (pm.response.code === 201 && body.token) {",
            "    pm.collectionVariables.set('studentToken', body.token);",
            "    pm.collectionVariables.set('token', body.token);",
            "    pm.collectionVariables.set('newUserId', String(body.data.id));",
            "}",
          ],
        }),
        item("Register a second student", "POST", "/register", {
          auth: false,
          body: `{
  "name": "Postman Second Student",
  "email": "{{secondUserEmail}}",
  "password": "{{demoPassword}}",
  "password_confirmation": "{{demoPassword}}"
}`,
          description: "Second student used later for the capacity conflict.",
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('second registration returns a bearer token', function () {",
            "    pm.expect(body.token).to.be.a('string').and.not.empty;",
            "    pm.expect(body.data.role.slug).to.eql('student');",
            "});",
            "if (pm.response.code === 201 && body.token) {",
            "    pm.collectionVariables.set('secondStudentToken', body.token);",
            "}",
          ],
        }),
        item("Current profile", "GET", "/users/me", {
          pre: useToken("studentToken"),
          description: "Authenticated profile for the token captured at registration.",
          sample: {
            name: "200 OK",
            code: 200,
            status: "OK",
            body: "{\n  \"data\": {\n    \"id\": 1,\n    \"name\": \"Postman Student\",\n    \"email\": \"postman.student@campus.test\",\n    \"role\": { \"slug\": \"student\" }\n  }\n}",
          },
          test: [
            ...statusLines(200),
            "const body = pm.response.json();",
            "pm.test('profile matches the registered student', function () {",
            "    pm.expect(body.data.email).to.eql(pm.collectionVariables.get('newUserEmail'));",
            "    pm.expect(body.data.role.slug).to.eql('student');",
            "    pm.expect(body.data.password).to.be.undefined;",
            "});",
          ],
        }),
        item("Register with missing fields", "POST", "/register", {
          auth: false,
          body: "{\n  \"name\": \"Incomplete\"\n}",
          description: "Validation failure. Does not save a token.",
          sample: {
            name: "422 Unprocessable",
            code: 422,
            status: "Unprocessable Entity",
            body: "{\n  \"message\": \"The email field is required.\",\n  \"errors\": {\n    \"email\": [\"The email field is required.\"],\n    \"password\": [\"The password field is required.\"]\n  }\n}",
          },
          test: [
            ...statusLines(422),
            "const body = pm.response.json();",
            "pm.test('missing registration fields are reported and no token is issued', function () {",
            "    pm.expect(body.errors).to.have.property('email');",
            "    pm.expect(body.errors).to.have.property('password');",
            "    pm.expect(body.token).to.be.undefined;",
            "});",
          ],
        }),
        item("Register with a submitted role", "POST", "/register", {
          auth: false,
          body: `{
  "name": "Role Grab",
  "email": "role.{{runStamp}}@campus.test",
  "password": "{{demoPassword}}",
  "password_confirmation": "{{demoPassword}}",
  "role": "administrator"
}`,
          description: "Public registration must reject a submitted role.",
          test: [
            ...statusLines(422),
            "pm.test('a submitted role is rejected and no token is issued', function () {",
            "    const body = pm.response.json();",
            "    pm.expect(body.errors).to.have.property('role');",
            "    pm.expect(body.token).to.be.undefined;",
            "});",
          ],
        }),
        item("Login with the wrong password", "POST", "/login", {
          auth: false,
          body: `{
  "email": "{{organizerEmail}}",
  "password": "not-the-demo-password"
}`,
          description: "Invalid credentials. Must not replace the saved token.",
          sample: {
            name: "401 Unauthorized",
            code: 401,
            status: "Unauthorized",
            body: "{\n  \"message\": \"The provided credentials are incorrect.\"\n}",
          },
          test: [
            "const tokenBefore = pm.collectionVariables.get('token');",
            ...statusLines(401),
            "pm.test('wrong password returns no token and keeps the previous token', function () {",
            "    pm.expect(pm.response.json().token).to.be.undefined;",
            "    pm.expect(pm.collectionVariables.get('token')).to.eql(tokenBefore);",
            "});",
          ],
        }),
        item("Login as organizer", "POST", "/login", {
          auth: false,
          body: `{
  "email": "{{organizerEmail}}",
  "password": "{{demoPassword}}"
}`,
          description: "Successful organizer login. Saves the token only when the status is 200.",
          sample: {
            name: "200 OK",
            code: 200,
            status: "OK",
            body: "{\n  \"message\": \"Login successful.\",\n  \"token\": \"<issued-on-success>\",\n  \"token_type\": \"Bearer\",\n  \"data\": { \"email\": \"organizer@campus.test\", \"role\": { \"slug\": \"organizer\" } }\n}",
          },
          test: [
            ...statusLines(200),
            "const body = pm.response.json();",
            "pm.test('organizer login returns a bearer token', function () {",
            "    pm.expect(body.message).to.eql('Login successful.');",
            "    pm.expect(body.token_type).to.eql('Bearer');",
            "    pm.expect(body.token).to.be.a('string').and.not.empty;",
            "    pm.expect(body.data.email).to.eql(pm.collectionVariables.get('organizerEmail'));",
            "    pm.expect(body.data.role.slug).to.eql('organizer');",
            "});",
            "if (pm.response.code === 200 && body.token) {",
            "    pm.collectionVariables.set('organizerToken', body.token);",
            "    pm.collectionVariables.set('token', body.token);",
            "    pm.collectionVariables.set('organizerName', body.data.name);",
            "}",
          ],
        }),
        item("Login as administrator", "POST", "/login", {
          auth: false,
          body: `{
  "email": "{{adminEmail}}",
  "password": "{{demoPassword}}"
}`,
          description: "Successful administrator login. Saves a separate admin token.",
          test: [
            ...statusLines(200),
            "const body = pm.response.json();",
            "pm.test('administrator login returns a bearer token', function () {",
            "    pm.expect(body.token).to.be.a('string').and.not.empty;",
            "    pm.expect(body.data.role.slug).to.eql('administrator');",
            "});",
            "if (pm.response.code === 200 && body.token) {",
            "    pm.collectionVariables.set('adminToken', body.token);",
            "}",
          ],
        }),
        item("Current profile without a token", "GET", "/users/me", {
          auth: false,
          description: "Unauthenticated profile request.",
          sample: {
            name: "401 Unauthorized",
            code: 401,
            status: "Unauthorized",
            body: "{\n  \"message\": \"Unauthenticated.\"\n}",
          },
          test: [
            ...statusLines(401),
            "pm.test('unauthenticated profile names the error', function () {",
            "    pm.expect(pm.response.json().message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
      ],
    },
    {
      name: "Events",
      item: [
        item("List events", "GET", "/events?per_page=5", {
          auth: false,
          description: "Public event list with pagination.",
          sample: {
            name: "200 OK",
            code: 200,
            status: "OK",
            body: "{\n  \"data\": [],\n  \"links\": {},\n  \"meta\": { \"current_page\": 1, \"per_page\": 5, \"total\": 0 }\n}",
          },
          test: [
            ...statusLines(200),
            "const body = pm.response.json();",
            "pm.test('event list has pagination fields', function () {",
            "    pm.expect(body.data).to.be.an('array');",
            "    pm.expect(body.meta.per_page).to.eql(5);",
            "    pm.expect(body.meta).to.have.property('current_page');",
            "    pm.expect(body.meta).to.have.property('total');",
            "});",
          ],
        }),
        item("List categories", "GET", "/categories?per_page=100", {
          auth: false,
          description: "Public category catalogue. Saves the first category for later event requests.",
          test: [
            ...statusLines(200),
            "const body = pm.response.json();",
            "pm.test('category list has data and pagination', function () {",
            "    pm.expect(body.data).to.be.an('array');",
            "    pm.expect(body.meta).to.have.property('total');",
            "});",
            "if (body.data && body.data.length > 0) {",
            "    pm.collectionVariables.set('categoryId', String(body.data[0].id));",
            "    pm.collectionVariables.set('categorySlug', body.data[0].slug);",
            "}",
          ],
        }),
        item("Create an event", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: eventBody,
          description: "Organizer creates a published future event.",
          sample: {
            name: "201 Created",
            code: 201,
            status: "Created",
            body: "{\n  \"data\": {\n    \"id\": 1,\n    \"title\": \"Postman Seminar\",\n    \"status\": \"published\",\n    \"venue\": \"Main Hall\",\n    \"banner\": null\n  }\n}",
          },
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('created event echoes the submitted title and venue', function () {",
            "    pm.expect(body.data.title).to.eql(pm.collectionVariables.get('eventTitle'));",
            "    pm.expect(body.data.venue).to.eql('Main Hall');",
            "    pm.expect(body.data.status).to.eql('published');",
            "    pm.expect(body.data.id).to.be.a('number');",
            "});",
            "if (pm.response.code === 201 && body.data) {",
            "    pm.collectionVariables.set('eventId', String(body.data.id));",
            "}",
          ],
        }),
        item("Get an event", "GET", "/events/{{eventId}}", {
          auth: false,
          description: "Public event details for the event created in this run.",
          test: [
            ...statusLines(200),
            "pm.test('event details match the created event', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(String(data.id)).to.eql(pm.collectionVariables.get('eventId'));",
            "    pm.expect(data.title).to.eql(pm.collectionVariables.get('eventTitle'));",
            "    pm.expect(data.organizer.name).to.eql(pm.collectionVariables.get('organizerName'));",
            "});",
          ],
        }),
        item("Get a missing event", "GET", "/events/999999999", {
          auth: false,
          description: "Unknown numeric event id.",
          sample: {
            name: "404 Not Found",
            code: 404,
            status: "Not Found",
            body: "{\n  \"message\": \"No query results for model [App\\\\Models\\\\Event] 999999999\"\n}",
          },
          test: [
            ...statusLines(404),
            "pm.test('missing event returns a message', function () {",
            "    pm.expect(pm.response.json().message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Search events by title", "GET", "/events?title={{eventTitle}}", {
          auth: false,
          description: "Title search for the event created in this run.",
          test: [
            ...statusLines(200),
            "pm.test('title search includes the created event', function () {",
            "    const titles = pm.response.json().data.map(function (event) { return event.title; });",
            "    pm.expect(titles).to.include(pm.collectionVariables.get('eventTitle'));",
            "});",
          ],
        }),
        item("Search events by organizer", "GET", "/events?organizer={{organizerName}}&title={{eventTitle}}", {
          auth: false,
          description: "Organizer-name search combined with the created title so the assertion does not depend on page size.",
          test: [
            ...statusLines(200),
            "pm.test('organizer search includes the created event', function () {",
            "    const rows = pm.response.json().data;",
            "    const match = rows.find(function (event) { return event.title === pm.collectionVariables.get('eventTitle'); });",
            "    pm.expect(match, 'created event').to.be.an('object');",
            "    pm.expect(match.organizer.name).to.eql(pm.collectionVariables.get('organizerName'));",
            "});",
          ],
        }),
        item("Filter events by category", "GET", "/events?category={{categorySlug}}&title={{eventTitle}}", {
          auth: false,
          description: "Category slug filter for the created event.",
          test: [
            ...statusLines(200),
            "pm.test('category filter includes the created event', function () {",
            "    const rows = pm.response.json().data;",
            "    const match = rows.find(function (event) { return event.title === pm.collectionVariables.get('eventTitle'); });",
            "    pm.expect(match, 'created event').to.be.an('object');",
            "    pm.expect(match.category.slug).to.eql(pm.collectionVariables.get('categorySlug'));",
            "});",
          ],
        }),
        item("Filter events by date", "GET", "/events?date={{futureDate}}&title={{eventTitle}}", {
          auth: false,
          description: "Exact event_date filter.",
          test: [
            ...statusLines(200),
            "pm.test('date filter returns the created event on that date', function () {",
            "    const rows = pm.response.json().data;",
            "    pm.expect(rows.length).to.be.above(0);",
            "    rows.forEach(function (event) {",
            "        pm.expect(event.event_date).to.eql(pm.collectionVariables.get('futureDate'));",
            "    });",
            "});",
          ],
        }),
        item("List upcoming events", "GET", "/events?timeframe=upcoming&title={{eventTitle}}", {
          auth: false,
          description: "Upcoming means published and event_date is today or later.",
          test: [
            ...statusLines(200),
            "pm.test('upcoming results include the future published event', function () {",
            "    const titles = pm.response.json().data.map(function (event) { return event.title; });",
            "    pm.expect(titles).to.include(pm.collectionVariables.get('eventTitle'));",
            "});",
          ],
        }),
        item("Reject an unknown timeframe", "GET", "/events?timeframe=yesterday", {
          auth: false,
          description: "timeframe only accepts upcoming or past.",
          test: [
            ...statusLines(422),
            "pm.test('unknown timeframe is a validation error', function () {",
            "    pm.expect(pm.response.json().errors).to.have.property('timeframe');",
            "});",
          ],
        }),
        item("Create a past event", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "{{pastTitle}}",
  "description": "A past event created by the Postman collection.",
  "category_id": {{categoryId}},
  "venue": "Library Lawn",
  "event_date": "{{pastDate}}",
  "start_time": "09:00:00",
  "end_time": "12:00:00",
  "maximum_capacity": 20,
  "registration_deadline": "{{pastDeadline}}",
  "status": "published"
}`,
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('past event is created', function () {",
            "    pm.expect(body.data.title).to.eql(pm.collectionVariables.get('pastTitle'));",
            "    pm.expect(body.data.event_date).to.eql(pm.collectionVariables.get('pastDate'));",
            "});",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('pastEventId', String(body.data.id));",
            "}",
          ],
        }),
        item("List past events", "GET", "/events?timeframe=past&title={{pastTitle}}", {
          auth: false,
          description: "Past means published and event_date is before today.",
          test: [
            ...statusLines(200),
            "pm.test('past results include the created past event', function () {",
            "    const titles = pm.response.json().data.map(function (event) { return event.title; });",
            "    pm.expect(titles).to.include(pm.collectionVariables.get('pastTitle'));",
            "});",
          ],
        }),
        item("Upcoming query excludes the past event", "GET", "/events?timeframe=upcoming&title={{pastTitle}}", {
          auth: false,
          test: [
            ...statusLines(200),
            "pm.test('upcoming results omit the past event', function () {",
            "    const titles = pm.response.json().data.map(function (event) { return event.title; });",
            "    pm.expect(titles).to.not.include(pm.collectionVariables.get('pastTitle'));",
            "});",
          ],
        }),
        item("Update an event", "PATCH", "/events/{{eventId}}", {
          pre: useToken("organizerToken"),
          body: "{\n  \"venue\": \"Library Lawn\"\n}",
          description: "Partial update.",
          test: [
            ...statusLines(200),
            "pm.test('patch changes only the submitted venue', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(data.venue).to.eql('Library Lawn');",
            "    pm.expect(data.title).to.eql(pm.collectionVariables.get('eventTitle'));",
            "});",
          ],
        }),
        item("Replace an event", "PUT", "/events/{{eventId}}", {
          pre: useToken("organizerToken"),
          body: eventBody,
          description: "Full replacement. All required event fields are sent.",
          test: [
            ...statusLines(200),
            "pm.test('put keeps the submitted event fields', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(data.title).to.eql(pm.collectionVariables.get('eventTitle'));",
            "    pm.expect(data.venue).to.eql('Main Hall');",
            "    pm.expect(data.maximum_capacity).to.eql(25);",
            "});",
          ],
        }),
        item("Create an event with missing fields", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: "{\n  \"title\": \"Incomplete event\"\n}",
          test: [
            ...statusLines(422),
            "pm.test('missing event fields are listed', function () {",
            "    const errors = pm.response.json().errors;",
            "    ['description', 'category_id', 'venue', 'event_date', 'start_time', 'end_time', 'maximum_capacity', 'registration_deadline', 'status'].forEach(function (field) {",
            "        pm.expect(errors, field).to.have.property(field);",
            "    });",
            "});",
          ],
        }),
        item("Create an event with an inconsistent schedule", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "Bad schedule {{runStamp}}",
  "description": "End time and deadline are inconsistent.",
  "category_id": {{categoryId}},
  "venue": "Main Hall",
  "event_date": "{{futureDate}}",
  "start_time": "12:00:00",
  "end_time": "09:00:00",
  "maximum_capacity": 10,
  "registration_deadline": "2027-06-15 18:00:00",
  "status": "published"
}`,
          test: [
            ...statusLines(422),
            "pm.test('end time and deadline are rejected', function () {",
            "    const errors = pm.response.json().errors;",
            "    pm.expect(errors).to.have.property('end_time');",
            "    pm.expect(errors).to.have.property('registration_deadline');",
            "});",
          ],
        }),
        item("Create an event as a student", "POST", "/events", {
          pre: useToken("studentToken"),
          body: eventBody,
          description: "Students cannot create events.",
          sample: {
            name: "403 Forbidden",
            code: 403,
            status: "Forbidden",
            body: "{\n  \"message\": \"This action is unauthorized.\"\n}",
          },
          test: [
            ...statusLines(403),
            "pm.test('student create is forbidden', function () {",
            "    pm.expect(pm.response.json().message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Create an event without a token", "POST", "/events", {
          auth: false,
          body: eventBody,
          test: [
            ...statusLines(401),
          ],
        }),
      ],
    },
    {
      name: "Registration",
      item: [
        item("Register for an event", "POST", "/events/{{eventId}}/registrations", {
          pre: useToken("studentToken"),
          description: "Authenticated student registers once.",
          sample: {
            name: "201 Created",
            code: 201,
            status: "Created",
            body: "{\n  \"message\": \"Registration successful.\",\n  \"data\": { \"id\": 1, \"event_id\": 1, \"user_id\": 2 }\n}",
          },
          test: [
            ...statusLines(201),
            "pm.test('registration records this user and event', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(String(data.event_id)).to.eql(pm.collectionVariables.get('eventId'));",
            "    pm.expect(String(data.user_id)).to.eql(pm.collectionVariables.get('newUserId'));",
            "});",
          ],
        }),
        item("Registration history", "GET", "/users/me/registrations?per_page=100", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(200),
            "pm.test('history includes the event just joined', function () {",
            "    const ids = pm.response.json().data.map(function (row) { return String(row.event_id); });",
            "    pm.expect(ids).to.include(pm.collectionVariables.get('eventId'));",
            "});",
          ],
        }),
        item("Register twice for the same event", "POST", "/events/{{eventId}}/registrations", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(409),
            "pm.test('duplicate registration explains the conflict', function () {",
            "    const body = pm.response.json();",
            "    pm.expect(body.reason).to.eql('already_registered');",
            "    pm.expect(body.message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Cancel a registration", "DELETE", "/events/{{eventId}}/registrations", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(200),
            "pm.test('cancellation returns a message', function () {",
            "    pm.expect(pm.response.json().message).to.eql('Registration cancelled.');",
            "});",
          ],
        }),
        item("Registration history after cancellation", "GET", "/users/me/registrations?per_page=100", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(200),
            "pm.test('cancelled event is no longer in the history', function () {",
            "    const ids = pm.response.json().data.map(function (row) { return String(row.event_id); });",
            "    pm.expect(ids).to.not.include(pm.collectionVariables.get('eventId'));",
            "});",
          ],
        }),
        item("Cancel when not registered", "DELETE", "/events/{{eventId}}/registrations", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(404),
            "pm.test('missing registration explains the error', function () {",
            "    const body = pm.response.json();",
            "    pm.expect(body.reason).to.eql('not_registered');",
            "    pm.expect(body.message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Register without a token", "POST", "/events/{{eventId}}/registrations", {
          auth: false,
          test: [
            ...statusLines(401),
          ],
        }),
        item("Create a one-seat event", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "Postman Capacity {{runStamp}}",
  "description": "Capacity check event.",
  "category_id": {{categoryId}},
  "venue": "Main Hall",
  "event_date": "{{futureDate}}",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "maximum_capacity": 1,
  "registration_deadline": "{{futureDeadline}}",
  "status": "published"
}`,
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('one-seat event is created', function () {",
            "    pm.expect(body.data.maximum_capacity).to.eql(1);",
            "});",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('capacityEventId', String(body.data.id));",
            "}",
          ],
        }),
        item("Take the only seat", "POST", "/events/{{capacityEventId}}/registrations", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(201),
            "pm.test('first student takes the only seat', function () {",
            "    pm.expect(String(pm.response.json().data.event_id)).to.eql(pm.collectionVariables.get('capacityEventId'));",
            "});",
          ],
        }),
        item("Register when the event is full", "POST", "/events/{{capacityEventId}}/registrations", {
          pre: useToken("secondStudentToken"),
          test: [
            ...statusLines(422),
            "pm.test('full event reports at_capacity', function () {",
            "    pm.expect(pm.response.json().reason).to.eql('at_capacity');",
            "});",
          ],
        }),
        item("Create a draft event", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "{{draftTitle}}",
  "description": "Draft event.",
  "category_id": {{categoryId}},
  "venue": "Main Hall",
  "event_date": "{{futureDate}}",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "maximum_capacity": 10,
  "registration_deadline": "{{futureDeadline}}",
  "status": "draft"
}`,
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('draftEventId', String(body.data.id));",
            "}",
            "pm.test('draft event is stored as draft', function () {",
            "    pm.expect(body.data.status).to.eql('draft');",
            "});",
          ],
        }),
        item("Register for a draft event", "POST", "/events/{{draftEventId}}/registrations", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(422),
            "pm.test('draft registration is not open', function () {",
            "    pm.expect(pm.response.json().reason).to.eql('not_open');",
            "});",
          ],
        }),
        item("Create an event with a passed deadline", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "Postman Closed {{runStamp}}",
  "description": "Deadline has passed.",
  "category_id": {{categoryId}},
  "venue": "Main Hall",
  "event_date": "{{closedDate}}",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "maximum_capacity": 10,
  "registration_deadline": "{{closedDeadline}}",
  "status": "published"
}`,
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('closedEventId', String(body.data.id));",
            "}",
          ],
        }),
        item("Register after the deadline", "POST", "/events/{{closedEventId}}/registrations", {
          pre: useToken("secondStudentToken"),
          test: [
            ...statusLines(422),
            "pm.test('passed deadline is reported', function () {",
            "    pm.expect(pm.response.json().reason).to.eql('deadline_passed');",
            "});",
          ],
        }),
      ],
    },
    {
      name: "Categories",
      item: [
        item("Get a category", "GET", "/categories/{{categoryId}}", {
          auth: false,
          test: [
            ...statusLines(200),
            "pm.test('category details match the saved category', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(String(data.id)).to.eql(pm.collectionVariables.get('categoryId'));",
            "    pm.expect(data.slug).to.eql(pm.collectionVariables.get('categorySlug'));",
            "});",
          ],
        }),
        item("Get a missing category", "GET", "/categories/999999999", {
          auth: false,
          test: [
            ...statusLines(404),
          ],
        }),
        item("Create a category as an organizer", "POST", "/categories", {
          pre: useToken("organizerToken"),
          body: "{\n  \"name\": \"Organizer Category {{runStamp}}\",\n  \"description\": \"Should be forbidden\"\n}",
          test: [
            ...statusLines(403),
          ],
        }),
        item("Create a category", "POST", "/categories", {
          pre: useToken("adminToken"),
          body: "{\n  \"name\": \"{{categoryName}}\",\n  \"description\": \"Temporary category\"\n}",
          sample: {
            name: "201 Created",
            code: 201,
            status: "Created",
            body: "{\n  \"data\": { \"id\": 7, \"name\": \"Postman Category\", \"slug\": \"postman-category\", \"description\": \"Temporary category\" }\n}",
          },
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "pm.test('created category generates a slug', function () {",
            "    pm.expect(body.data.name).to.eql(pm.collectionVariables.get('categoryName'));",
            "    pm.expect(body.data.slug).to.be.a('string').and.not.empty;",
            "});",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('managedCategoryId', String(body.data.id));",
            "}",
          ],
        }),
        item("Create a category with a slug", "POST", "/categories", {
          pre: useToken("adminToken"),
          body: "{\n  \"name\": \"Slug Rejected {{runStamp}}\",\n  \"slug\": \"custom-slug\"\n}",
          test: [
            ...statusLines(422),
            "pm.test('client slug is prohibited', function () {",
            "    pm.expect(pm.response.json().errors).to.have.property('slug');",
            "});",
          ],
        }),
        item("Replace a category without a name", "PUT", "/categories/{{managedCategoryId}}", {
          pre: useToken("adminToken"),
          body: "{\n  \"description\": \"Missing the name\"\n}",
          test: [
            ...statusLines(422),
            "pm.test('put requires the category name', function () {",
            "    pm.expect(pm.response.json().errors).to.have.property('name');",
            "});",
          ],
        }),
        item("Replace a category", "PUT", "/categories/{{managedCategoryId}}", {
          pre: useToken("adminToken"),
          body: "{\n  \"name\": \"{{categoryName}} Updated\",\n  \"description\": \"Replaced\"\n}",
          test: [
            ...statusLines(200),
            "pm.test('put updates the category name', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(data.name).to.eql(pm.collectionVariables.get('categoryName') + ' Updated');",
            "    pm.expect(data.slug).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Update a category", "PATCH", "/categories/{{managedCategoryId}}", {
          pre: useToken("adminToken"),
          body: "{\n  \"description\": \"Updated description\"\n}",
          test: [
            ...statusLines(200),
            "pm.test('patch updates the description', function () {",
            "    pm.expect(pm.response.json().data.description).to.eql('Updated description');",
            "});",
          ],
        }),
        item("Create an event in the new category", "POST", "/events", {
          pre: useToken("organizerToken"),
          body: `{
  "title": "Postman Blocker {{runStamp}}",
  "description": "Holds the category so delete conflicts.",
  "category_id": {{managedCategoryId}},
  "venue": "Main Hall",
  "event_date": "{{futureDate}}",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "maximum_capacity": 5,
  "registration_deadline": "{{futureDeadline}}",
  "status": "published"
}`,
          test: [
            ...statusLines(201),
            "const body = pm.response.json();",
            "if (pm.response.code === 201) {",
            "    pm.collectionVariables.set('blockerEventId', String(body.data.id));",
            "}",
          ],
        }),
        item("Delete a category that still has events", "DELETE", "/categories/{{managedCategoryId}}", {
          pre: useToken("adminToken"),
          sample: {
            name: "409 Conflict",
            code: 409,
            status: "Conflict",
            body: "{\n  \"message\": \"This category cannot be deleted while events still use it.\"\n}",
          },
          test: [
            ...statusLines(409),
            "pm.test('category delete conflict explains the reason', function () {",
            "    pm.expect(pm.response.json().message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("Delete the blocking event", "DELETE", "/events/{{blockerEventId}}", {
          pre: useToken("organizerToken"),
          test: [
            ...statusLines(204),
          ],
        }),
        item("Delete a category", "DELETE", "/categories/{{managedCategoryId}}", {
          pre: useToken("adminToken"),
          test: [
            ...statusLines(204),
          ],
        }),
        item("Get the deleted category", "GET", "/categories/{{managedCategoryId}}", {
          auth: false,
          test: [
            ...statusLines(404),
          ],
        }),
      ],
    },
    {
      name: "Users",
      item: [
        item("List users as a student", "GET", "/users", {
          pre: useToken("studentToken"),
          test: [
            ...statusLines(403),
            "pm.test('student user list is forbidden', function () {",
            "    pm.expect(pm.response.json().message).to.be.a('string').and.not.empty;",
            "});",
          ],
        }),
        item("List users without a token", "GET", "/users", {
          auth: false,
          test: [
            ...statusLines(401),
          ],
        }),
        item("List users", "GET", "/users", {
          pre: useToken("adminToken"),
          description: "Administrator user list.",
          test: [
            ...statusLines(200),
            "pm.test('user list returns profiles with roles', function () {",
            "    const rows = pm.response.json().data;",
            "    pm.expect(rows).to.be.an('array').that.is.not.empty;",
            "    pm.expect(rows[0]).to.have.property('email');",
            "    pm.expect(rows[0].role).to.have.property('slug');",
            "    pm.expect(rows[0].password).to.be.undefined;",
            "});",
          ],
        }),
        item("Get a user", "GET", "/users/{{newUserId}}", {
          pre: useToken("adminToken"),
          test: [
            ...statusLines(200),
            "pm.test('user details match the registered student', function () {",
            "    const data = pm.response.json().data;",
            "    pm.expect(String(data.id)).to.eql(pm.collectionVariables.get('newUserId'));",
            "    pm.expect(data.email).to.eql(pm.collectionVariables.get('newUserEmail'));",
            "});",
          ],
        }),
        item("Get a missing user", "GET", "/users/999999999", {
          pre: useToken("adminToken"),
          test: [
            ...statusLines(404),
          ],
        }),
        item("Change a user role", "PATCH", "/users/{{newUserId}}/role", {
          pre: useToken("adminToken"),
          body: "{\n  \"role\": \"organizer\"\n}",
          sample: {
            name: "200 OK",
            code: 200,
            status: "OK",
            body: "{\n  \"data\": { \"id\": 1, \"role\": { \"slug\": \"organizer\" } }\n}",
          },
          test: [
            ...statusLines(200),
            "pm.test('role slug matches the submitted role', function () {",
            "    pm.expect(pm.response.json().data.role.slug).to.eql('organizer');",
            "});",
          ],
        }),
        item("Restore the student role", "PATCH", "/users/{{newUserId}}/role", {
          pre: useToken("adminToken"),
          body: "{\n  \"role\": \"student\"\n}",
          test: [
            ...statusLines(200),
            "pm.test('role is restored to student', function () {",
            "    pm.expect(pm.response.json().data.role.slug).to.eql('student');",
            "});",
          ],
        }),
      ],
    },
    {
      name: "Cleanup",
      item: [
        item("Delete the main event", "DELETE", "/events/{{eventId}}", {
          pre: useToken("organizerToken"),
          test: [...statusLines(204)],
        }),
        item("Delete the past event", "DELETE", "/events/{{pastEventId}}", {
          pre: useToken("organizerToken"),
          test: [...statusLines(204)],
        }),
        item("Delete the draft event", "DELETE", "/events/{{draftEventId}}", {
          pre: useToken("organizerToken"),
          test: [...statusLines(204)],
        }),
        item("Delete the closed event", "DELETE", "/events/{{closedEventId}}", {
          pre: useToken("organizerToken"),
          test: [...statusLines(204)],
        }),
        item("Delete the one-seat event", "DELETE", "/events/{{capacityEventId}}", {
          pre: useToken("organizerToken"),
          test: [...statusLines(204)],
        }),
        item("Get the deleted event", "GET", "/events/{{eventId}}", {
          auth: false,
          test: [...statusLines(404)],
        }),
        item("Logout", "POST", "/logout", {
          pre: useToken("organizerToken"),
          sample: {
            name: "200 OK",
            code: 200,
            status: "OK",
            body: "{\n  \"message\": \"Logged out.\"\n}",
          },
          test: [
            ...statusLines(200),
            "pm.test('logout confirms the session ended', function () {",
            "    pm.expect(pm.response.json().message).to.eql('Logged out.');",
            "});",
          ],
        }),
        item("Current profile after logout", "GET", "/users/me", {
          pre: useToken("organizerToken"),
          description: "The organizer token was revoked by logout.",
          test: [
            ...statusLines(401),
          ],
        }),
      ],
    },
  ],
};

const output = path.resolve(__dirname, "Campus-Events-API.postman_collection.json");
fs.writeFileSync(output, JSON.stringify(collection, null, 2));
console.log("Wrote " + output);
console.log("Requests " + collection.item.reduce((sum, folder) => sum + folder.item.length, 0));
