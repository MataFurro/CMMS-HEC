
## Stitch MCP Configuration

This project includes a configuration for the Stitch MCP server.

**File:** `mcp-servers.json`

**setup:**
1. Ensure your API Key is valid.
2. For better security, consider moving the API Key to an environment variable if your MCP client supports it (e.g. `STITCH_API_KEY`).
3. The current configuration uses the provided key directly in `mcp-servers.json` as requested.

**Usage:**
Configure your MCP client (like Claude Desktop or VS Code extension) to use the `mcp-servers.json` file or copy its content to your client's configuration.
