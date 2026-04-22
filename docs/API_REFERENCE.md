# REST API Documentation

## Overview

The AI Assistant system exposes a RESTful API for managing:
- User authentication (registration, login)
- Conversations (create, list, retrieve, update, delete)
- Messages (add, retrieve conversation history)

All responses are JSON and include standard HTTP status codes.

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": "uuid",
    "name": "example"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Invalid input",
  "errors": {
    "email": "Email is required"
  }
}
```

## API Endpoints

### Health Check
**GET** `/api/health`

Check API server status.

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "status": "ok",
    "timestamp": "2026-04-21T21:20:00+00:00"
  }
}
```

### Authentication

#### Register User
**POST** `/api/auth/register`

Create a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe"
}
```

**Validation:**
- email: Required, valid email format
- password: Required, minimum 8 characters
- name: Required, non-empty string

**Response (201):**
```json
{
  "success": true,
  "message": "Created"
}
```

**Response (400):**
```json
{
  "success": false,
  "message": "Invalid email format",
  "errors": {
    "email": "Invalid email format"
  }
}
```

#### Login
**POST** `/api/auth/login`

Authenticate user and receive JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "token": "eyJhbGc...",
    "expiresIn": 3600
  }
}
```

**Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### Conversations

#### Create Conversation
**POST** `/api/conversations`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Chat with AI",
  "aiModel": "gpt-4",
  "description": "Optional description"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Created",
  "data": {
    "id": "uuid",
    "title": "Chat with AI",
    "aiModel": "gpt-4",
    "createdAt": "2026-04-21T21:20:00+00:00"
  }
}
```

#### List User's Conversations
**GET** `/api/conversations`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `limit` (optional): Default 20, max 100
- `offset` (optional): Default 0

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": "uuid",
      "title": "Chat with AI",
      "aiModel": "gpt-4",
      "messageCount": 5,
      "createdAt": "2026-04-21T21:20:00+00:00",
      "updatedAt": "2026-04-21T21:25:00+00:00"
    }
  ]
}
```

#### Get Conversation
**GET** `/api/conversations/{conversationId}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": "uuid",
    "title": "Chat with AI",
    "aiModel": "gpt-4",
    "description": "Optional description",
    "messageCount": 5,
    "active": true,
    "createdAt": "2026-04-21T21:20:00+00:00",
    "updatedAt": "2026-04-21T21:25:00+00:00"
  }
}
```

**Response (404):**
```json
{
  "success": false,
  "message": "Not Found"
}
```

#### Update Conversation
**PUT** `/api/conversations/{conversationId}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Updated Title",
  "aiModel": "claude-3",
  "description": "New description"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": "uuid",
    "title": "Updated Title",
    "aiModel": "claude-3"
  }
}
```

#### Delete Conversation
**DELETE** `/api/conversations/{conversationId}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204):** No content

**Response (404):**
```json
{
  "success": false,
  "message": "Not Found"
}
```

---

### Messages

#### Add Message to Conversation
**POST** `/api/conversations/{conversationId}/messages`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "content": "Hello, what can you help me with?",
  "role": "user"
}
```

**Note:** `role` can be `user` or `assistant`. User messages are from the human, assistant messages are from the AI.

**Response (201):**
```json
{
  "success": true,
  "message": "Created",
  "data": {
    "id": "uuid",
    "conversationId": "uuid",
    "role": "user",
    "content": "Hello, what can you help me with?",
    "createdAt": "2026-04-21T21:20:00+00:00"
  }
}
```

#### Get Conversation Messages
**GET** `/api/conversations/{conversationId}/messages`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `limit` (optional): Default 50, max 200
- `offset` (optional): Default 0

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": "uuid",
      "role": "user",
      "content": "Hello, what can you help me with?",
      "createdAt": "2026-04-21T21:20:00+00:00"
    },
    {
      "id": "uuid",
      "role": "assistant",
      "content": "I can help you with...",
      "createdAt": "2026-04-21T21:20:05+00:00"
    }
  ]
}
```

---

## Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200  | OK | Successful GET/PUT |
| 201  | Created | Successful POST |
| 204  | No Content | Successful DELETE |
| 400  | Bad Request | Invalid input |
| 401  | Unauthorized | Missing or invalid token |
| 403  | Forbidden | User doesn't own resource |
| 404  | Not Found | Resource doesn't exist |
| 500  | Internal Server Error | Server error |

---

## Authentication

All endpoints except `/api/health`, `/api/auth/register`, and `/api/auth/login` require authentication via JWT token in the `Authorization` header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Tokens expire after 24 hours. Use the refresh endpoint (coming soon) to get a new token.

---

## Error Handling

All errors follow the standard error response format:

```json
{
  "success": false,
  "message": "Human-readable error message",
  "errors": {
    "field": "Field-specific error"
  }
}
```

Validation errors include a map of field-specific errors. Other errors just include the message.

---

## Example Usage

### 1. Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123","name":"John Doe"}'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

### 3. Create Conversation
```bash
curl -X POST http://localhost:8000/api/conversations \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title":"New Chat","aiModel":"gpt-4"}'
```

### 4. Send Message
```bash
curl -X POST http://localhost:8000/api/conversations/{conversationId}/messages \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"content":"Hello!","role":"user"}'
```
