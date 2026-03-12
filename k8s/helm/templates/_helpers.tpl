{{- define "wp-stack.name" -}}
roleagroecologico
{{- end }}

{{- define "wp-stack.fullname" -}}
{{ .Release.Name }}-wp-stack
{{- end }}