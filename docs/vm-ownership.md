# VM Ownership And Rotation

Track individual VM role ownership for each milestone/sprint.

Rotation is recommended, not required.

## Anticipated Uptime Schedule

Document expected VM service availability windows, especially if the VM is not intended to run 24/7.

| VM Or Service | Primary Owner | Uptime Plan | Typical Downtime Window | Recovery/Restart Owner | Notes |
| --- | --- | --- | --- | --- | --- |
| APP VM | Noaman S. + Rosmy A. | **24/7** | **2 Hours 10pm - 12pm** | Noaman S. + Rosmy A. | Noaman and Rosmy will be in charge of keeping VMs up 24/7 **EXCEPT** on weekends where there will be 2 hours of downtime. |
| DB VM | Tristen D. | **24/7** | **2 Hours 10pm - 12pm** | Tristen D. | Tristen will be in charge of keeping VMs up 24/7 **EXCEPT** on weekends where there will be 2 hours of downtime. |
| MQ VM | Caitlin O. | **24/7** | **2 Hours 10pm - 12pm** | Caitlin O. | Caitlin will be in charge of keeping VMs up 24/7 **EXCEPT** on weekends where there will be 2 hours of downtime. |
| API VM | Xaidyn L. | **24/7** | **2 Hours 10pm - 12pm** | Xaidyn L. | Xaidyn will be in charge of keeping VMs up 24/7 **EXCEPT** on weekends where there will be 2 hours of downtime. |

Use this section for operations planning and communication only. This tracks expected VM uptime, not individual attendance.

## Current Ownership

| Team Member | App Role | MQ Role | DB Role | API Role | Notes |
| --- | --- | --- | --- | --- | --- |
| Noaman S. | ✓ | ✗ | ✗ | ✗ | Noaman will be working on App Role for remainder of this project.|
| Rosmy A. | ✓ | ✗ | ✗ | ✗ | Rosmy will be working on App Role for remainder of this project. |
| Caitlin O. | ✗ | ✓ | ✗ | ✗ | Caitlin will be working on MQ Role for remainder of this project. |
| Xaidyn L. | ✗ | ✗ | ✗ | ✓ | Xaidyn will be working on API Role for remainder of this project. |
| Tristen D. | ✗ | ✗ | ✓ | ✗ | Tristen will be working on DB Role for remainder of this project. |

## Rotation History

| Milestone Or Sprint | Team Member | Previous Role(s) | New Role(s) | Reason |
| --- | --- | --- | --- | --- |
| All Milestones  | Noaman S. | APP  | APP | In order to provide efficncy **Noaman** will be in charge of the VM Ownership of APP |
| All Milestones | Rosmy A. |  | APP  | APP | In order to provide efficncy **Rosmy** will be in charge of the VM Ownership of APP |
| All Milestones | Caitlin O. | RABIT MQ | RABIIT MQ | In order to provide efficiency **Caitlin** will be in charge of the VM Ownership of RABBIT MQ |
| All Milestones |  Xaidyn L.  | API WORKER | API WORKER | In order to provide efficncy **Xaidyn** will be in charge of the VM Ownership of API |
| All Milestones | Tristen D. | DB | DB | In order to provide efficncy **Tristen** will be in charge of the VM Ownership of DB |

## Role Definitions

- App_Dev: application-layer implementation and integration
- MQ_QA: message-queue verification, queue health checks, and message-flow testing
- DB_Dev: schema, migrations, persistence logic, and data validation
- API_QA: API contract checks, response validation, and integration test support
