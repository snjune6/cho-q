# 초큐 (Cho-Q) — AI Agent Guide

> **"초보의 속사정을 큐알(QR)로!"**

초보 운전자가 차량 뒷유리에 QR코드를 붙이면, 주변 운전자가 스캔해 **현재 차 안 상황**(멘탈 상태, 미안함, 감사 등)을 확인하고 **따뜻한 한마디**를 남길 수 있는 모바일 웹 서비스.

---

## 1. 핵심 기능

| 기능 | 설명 | URL |
|------|------|-----|
| **상태 대시보드** | QR 스캔 시 운전자 현재 상태·커스텀 메시지 표시 (모바일 최적화) | `/c/{carCode}` |
| **운전자 콘솔** | 차 안에서 버튼 한 번으로 상태 변경 | `/console/{carCode}` |
| **따뜻한 한마디** | 뒤차 운전자가 익명 응원 메시지 전송 | `/c/{carCode}` 하단 |

### 상태 프리셋 (기본값)

| key | 라벨 | 아이콘 |
|-----|------|--------|
| `parking` | 주차 연습 중… 땀 뻘뻘 | 🅿️ |
| `hill_start` | 언덕길 출발 대기 중 | ⛰️ |
| `sorry` | 죄송해요, 천천히 갈게요 | 🙏 |
| `thanks` | 감사합니다! | 💛 |
| `nervous` | 긴장 중이에요 | 😰 |
| `custom` | 직접 입력 메시지 | ✏️ |

---

## 2. 기술 스택 (확정)

| 영역 | 선택 | 비고 |
|------|------|------|
| **언어** | PHP 8.1+ | 카페24 PHP 호스팅 기준 |
| **DB** | MariaDB 10.x | 카페24 제공 DB (MySQL 호환) |
| **프론트** | Vanilla HTML/CSS/JS | 공유호스팅에 Node 빌드 불필요 |
| **실시간** | AJAX 폴링 (3초) | WebSocket 미지원 → `/api/poll.php` |
| **배포** | 카페24 PHP 호스팅 | Apache + `.htaccess` URL 리라이트 |

> 이전 기획의 Next.js / Supabase / Vercel 스택은 **사용하지 않음**. 아래 디렉토리 구조와 API 규칙을 따를 것.

---

## 3. 디렉토리 구조

```text
cho-q/                      ← 카페24 웹루트(/)에 그대로 업로드
├── index.php               # 프론트 컨트롤러 (라우팅)
├── .htaccess               # Apache 리라이트 + config 보호
├── .gitignore
├── AGENTS.md
├── config/
│   ├── config.example.php  # 설정 템플릿 (커밋 O)
│   └── config.php          # 실제 설정 (커밋 X, 서버에서 생성)
├── includes/
│   ├── bootstrap.php       # 공통 초기화
│   ├── db.php              # PDO 연결
│   └── helpers.php         # 유틸·상태 프리셋
├── api/
│   ├── status.php          # GET/POST 운전자 상태
│   ├── messages.php        # GET/POST 방명록
│   └── poll.php            # 폴링 (상태+메시지 변경 감지)
├── views/
│   ├── layout.php          # 공통 레이아웃
│   ├── home.php            # 랜딩
│   ├── car.php             # QR 스캔 뷰
│   └── console.php         # 운전자 콘솔
├── assets/
│   ├── css/app.css
│   └── js/app.js
└── sql/
    └── schema.sql          # 초기 테이블 DDL
```

---

## 4. 데이터베이스 스키마

### `cars` — 차량(운전자) 등록

| 컬럼 | 타입 | 설명 |
|------|------|------|
| `id` | INT PK AI | |
| `car_code` | VARCHAR(32) UNIQUE | URL용 코드 (예: `busan1234`) |
| `pin_hash` | VARCHAR(255) | 콘솔 접근 PIN (password_hash) |
| `created_at` | DATETIME | |

### `driver_status` — 현재 상태 (차량당 1행)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| `car_id` | INT PK FK | |
| `status_key` | VARCHAR(32) | 프리셋 key 또는 `custom` |
| `custom_message` | VARCHAR(200) | custom일 때 표시 문구 |
| `updated_at` | DATETIME | 폴링 기준 시각 |

### `guest_messages` — 따뜻한 한마디

| 컬럼 | 타입 | 설명 |
|------|------|------|
| `id` | INT PK AI | |
| `car_id` | INT FK | |
| `message` | VARCHAR(200) | 익명 메시지 |
| `created_at` | DATETIME | |

---

## 5. API 규칙

모든 API는 JSON 응답. `Content-Type: application/json; charset=utf-8`

### `GET /api/status.php?car={carCode}`

```json
{ "status_key": "parking", "custom_message": "", "updated_at": "2026-07-14T12:00:00+09:00" }
```

### `POST /api/status.php` (콘솔 인증 필요)

Body: `{ "car": "busan1234", "pin": "1234", "status_key": "sorry", "custom_message": "" }`

### `GET /api/messages.php?car={carCode}&limit=20`

```json
{ "messages": [{ "id": 1, "message": "화이팅!", "created_at": "..." }] }
```

### `POST /api/messages.php`

Body: `{ "car": "busan1234", "message": "천천히 가셔도 돼요!" }`

### `GET /api/poll.php?car={carCode}&since={ISO8601}`

상태·메시지 변경 시에만 응답. 변경 없으면 `{ "changed": false }`.

---

## 6. 마일스톤 (Milestones)

-[  ] 1단계: Supabase 연동 및 상태 변경 API 구현
- [ ] 2단계: 운전자용 제어 패널 및 QR 스캔 시 노출되는 상태 페이지 UI 구현
- [ ] 3단계: QR 코드 생성기 기능 추가 (자신의 차량 등록 및 QR 이미지 다운로드)
- [ ] 4단계: 실시간 응원 방명록 기능 붙이기

### 로컬 개발

- PHP 내장 서버: `php -S localhost:8080 router.php` (프로젝트 루트에서)
- DB: 로컬 MariaDB/MySQL + `config.php`의 `env`를 `local`로 설정

---

## 7. 카페24 배포 체크리스트

1. **호스팅**: PHP 8.1 이상, MariaDB 생성
2. **DB**: `sql/schema.sql` 실행 (phpMyAdmin)
3. **설정**: `config/config.example.php` → `config/config.php` 복사 후 DB 정보 입력
   - DB 호스트: 카페24에서 제공 (보통 `localhost` 또는 전용 호스트명)
   - `base_url`: 실제 도메인 (HTTPS 필수 — QR 인식)
4. **업로드**: 프로젝트 전체를 웹루트(`/`)에 FTP/SFTP 업로드
5. **권한**: `config/config.php`는 웹에서 직접 접근 불가 (`.htaccess`로 차단됨)
6. **QR URL 형식**: `https://도메인/c/{carCode}`

### 로컬 개발

- PHP 내장 서버: `php -S localhost:8080 router.php` (프로젝트 루트에서)
- DB: 로컬 MariaDB/MySQL + `config.php`의 `env`를 `local`로 설정

---

## 8. AI 에이전트 개발 지침

### 코드 원칙

- **Mobile First**: 기준 너비 390px, 세로 화면 우선
- **PHP only**: 프레임워크 없이 순수 PHP + PDO. Composer는 선택
- **보안**: PIN은 `password_hash` / `password_verify`. SQL은 반드시 prepared statement
- **실시간**: Supabase Realtime 대신 **3초 폴링** (`assets/js/app.js`의 `ChoQ.poll()`)
- **카페24 호환**: `exec`, `shell_exec`, Node, WebSocket 사용 금지

### UI/UX

- 둥글둥글한 **파스텔톤** UI, 귀여운 상태 아이콘
- **다크모드** 토글 지원 (`prefers-color-scheme` + 수동 토글)
- 야간 운전 배려: 기본 대비 낮춤, 다크모드 권장

### 새 기능 추가 시

1. DB 변경 → `sql/schema.sql` + 마이그레이션 주석
2. API 추가 → `api/` 하위, JSON 규칙 유지
3. 페이지 추가 → `views/` + `index.php` 라우트 등록
4. 이 문서(AGENTS.md) 해당 섹션 업데이트
