Finisher DownloadFileStream
===========================

Sends the file of the task result to the browser and ends the request. It is
meant for export tasks in the backend module, with a target that writes a
temporary file (`DataTargetFileStream`, `DataTargetXMLStream` with
`output: file`). The location of the file is taken from the info of the task
result. If there is no such file, nothing is sent.

Use it as the last finisher: the request ends after the file was sent, so
finishers after it are not run.

### Example

```yaml
export:
  tasks:
    example:
      # [...]
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\DownloadFileStream"
          config:
            type: "application/xml"
            filename: "news_feed"
            fileExt: "xml"
```

### Options

| option          | type   | required | default                    | description |
|-----------------|--------|----------|----------------------------|-------------|
| config.type     | string | no       | `application/octet-stream` | content type of the response |
| config.filename | string | no       | `file_` and the time       | file name without extension |
| config.fileExt  | string | no       | `.dat`                     | file extension, with or without leading dot |
