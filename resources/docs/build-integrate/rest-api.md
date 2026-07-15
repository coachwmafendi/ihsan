# REST API

The Ihsan REST API lets you read and manage donations, campaigns, donors, and subscriptions programmatically.

![Elements list](/images/docs/app-elements.png)

## Authentication

Authenticate using a bearer token created from the **API keys** section of your organization settings. Include the token in the `Authorization` header of every request.

## Example request

```bash
curl https://your-domain.test/api/v1/donations \
    -H "Authorization: Bearer {token}"
```

## Conventions

- Requests return JSON responses.
- List endpoints support pagination, filtering, and sorting.
- Rate limits apply to all API keys.

Refer to the endpoint documentation inside the dashboard for the full schema and available parameters.
