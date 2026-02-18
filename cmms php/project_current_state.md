# HEC CMMS Project Current State (2026)

## Overview
The current project is a custom-built CMMS (Computerized Maintenance Management System) for HEC, implemented in PHP with a MySQL/MariaDB backend. It has evolved from the 2020 SaaS proposal into a full-stack local application.

## Core Architecture
- **Backend Architecture**: Follows a Repository/Provider pattern.
  - `Repositories/`: Direct PDO access to MySQL.
  - `Providers/`: Layer used by the frontend to consume data.
  - `Models/`: Entity definitions (e.g., `WorkOrderEntity`, `AssetEntity`).
- **Database**:
  - Primary: MySQL (Handles Assets, Work Orders, Users).
  - Secondary: SQLite (Used for `messenger_reports` via `API Mail/database/messenger.db`).

## Key Components
- **Asset Management (`AssetProvider.php`)**:
  - Manages biomedical assets, families, and financial metrics (ROI, TCO).
  - Integrates reliability metrics like `Useful Life %` and `Availability`.
- **Work Order Lifecycle (`WorkOrderProvider.php`)**:
  - Handles the creation of OTs (Órdenes de Trabajo) from user requests.
  - Implements a "Feedback Loop" that updates request status when an OT is completed.
- **Messenger/Requests (`messenger_requests.php`)**:
  - Entry point for clinical users to report incidents.
  - Integration between SQLite requests and MySQL OTs.

## Strategic Alignment
The system continues to use the risk-based priorization (Fennigkoh & Smith) and Weibull distribution concepts mentioned in the 2020 documentation, but now implemented directly in the custom PHP backend.
