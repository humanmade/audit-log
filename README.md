<table width="100%">
	<tr>
		<td align="left" width="70">
			<strong>Audit Log</strong><br />
			Tamper resistant, off-site audit logging for WordPress. <a href="https://hmn.slack.com/messages/">#</a>
		</td>
		<td rowspan="2" width="20%">
			<img src="https://hmn.md/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>

# Audit Log

<img src="https://joehoyle-captured.s3.amazonaws.com/fzmPltf0.png" width="500" />

This plugin provides a temper resistant, off-site audit log for WordPress. It achieves this by storing the data in a separate DynamoDB table outside of the WordPress database. This means the audit log survives database restores, site re-installs, rogue malicious behavior and other events that would threaten the integrity of the audit log.

## Configuration

Define the following PHP constants to configure the Audit Log:

- `AUDIT_LOG_SQS_QUEUE_URL`
- `AUDIT_LOG_DYNAMO_DB_TABLE`
