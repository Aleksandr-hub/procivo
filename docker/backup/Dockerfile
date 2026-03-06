FROM postgres:18-alpine

RUN apk add --no-cache aws-cli bash

COPY backup.sh /usr/local/bin/backup.sh
COPY restore-test.sh /usr/local/bin/restore-test.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/*.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
