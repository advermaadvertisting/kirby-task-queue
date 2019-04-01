# Kirby Task Queue
The task queue allows to put tasks into a queue that can be executed
asynchronously.

## Configuration
To be able to perform tasks in the background one storage engine needs to be
configured. Two options are available:

- `redis`: use a redis server as the storage engine
- `pdo`: use any supported database that PDO supports as a storage engine.

They can be configured by setting the `adverma.taskQueue.storage` value to
one of the engines above.

For each option, take a look at the specific configuration parameters below.

Additionally a secret is required which is needed to prevent the queue from
being triggered by third party requests.
For this the `adverma.taskQueue.secret` needs to be set.
The CURL request that triggeres the queue needs to include the same value
inside the `API-Key` HTTP header:

```
curl -X "POST" "http://{url to the task queue endpoint}/task-queue" \
     -H 'API-Key: foo'
```

If no secret is set, or the secret does not match, the `task-queue` endpoint
will return a not found response.

### Redis
The redis configuration should work out of the box and no additional
configuration is needed.

The following options to connect to a redis instance are available:

- `adverma.taskQueue.redis.host` - the name of the host to connect to
- `adverma.taskQueue.redis.port` - the port on which the redis server is available.

### PDO
For PDO (postgresql) the following table needs to be created:

```
CREATE TABLE "tasks" (
  "taskIdentifier" VARCHAR( 255 ) NOT NULL PRIMARY KEY,
  "jobClass" VARCHAR( 255 ) NOT NULL,
  "payload" TEXT,
  "createdAt" TIMESTAMP NOT NULL,
  "startedAt" TIMESTAMP,
  "completedAt" TIMESTAMP,
  "result" BOOL,
  "message" TEXT
);
```

Once the database table is available, the following options to connect to a
PDO backend are available:

- `adverma.taskQueue.pdo.dsn` - the DSN contains the information required to connect
  to the database.
- `adverma.taskQueue.username` - the username that should be used for the connection.
- `adverma.taskQueue.password` - the password that should be used for the connection.
