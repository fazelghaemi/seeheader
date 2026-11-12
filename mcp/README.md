# SeeHeader MCP for Codex

This local stdio MCP server connects Codex to the SeeHeader settings and icon library on your WordPress site. It requires Node.js 18 or newer and the active SeeHeader plugin.

1. In WordPress, create an **Application Password** for an administrator account under **Users → Profile**.
2. Set `SEEHEADER_SITE_URL` to the HTTPS site root, `SEEHEADER_USER` to that username, and `SEEHEADER_APP_PASSWORD` to the application password in your Codex environment. Do not put the password in this repository.
3. Add this entry to `~/.codex/config.toml` (replace the path):

```toml
[mcp_servers.seeheader]
command = "node"
args = ["D:/ReadyStudio/SeeMenu/seemenu-git/seeheader/mcp/server.mjs"]
env_vars = ["SEEHEADER_SITE_URL", "SEEHEADER_USER", "SEEHEADER_APP_PASSWORD"]
```

Restart Codex and check `codex mcp list`. The tools can read current settings, list the dynamically discovered SVG icons, update selected settings, and replace the built menu. WordPress checks `manage_options` on every REST request. The server does not expose credentials to MCP tool responses.
