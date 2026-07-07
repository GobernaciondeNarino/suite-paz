#!/bin/sh
# Uso: scripts/gitpush.sh <rama>
# Empuja usando el token del scratchpad vía GIT_ASKPASS (nunca lo imprime ni lo guarda en config).
SP="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad"
BRANCH="${1:-actualizar-paz}"
ASK="$SP/askpass.sh"
if [ ! -f "$ASK" ]; then printf '#!/bin/sh\ncat "%s/.gh_token"\n' "$SP" > "$ASK"; chmod +x "$ASK"; fi
git remote set-url origin "https://x-access-token@github.com/GobernaciondeNarino/suite-paz.git"
GIT_TERMINAL_PROMPT=0 GIT_ASKPASS="$ASK" git push -u origin "$BRANCH"
