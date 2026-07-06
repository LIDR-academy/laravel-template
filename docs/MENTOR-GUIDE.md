# Guía del Mentor — Workshop "TDD con IA gobernada" (Laravel)

> **Para quién:** otro mentor que toma este repositorio y debe impartir el workshop sin
> contexto previo. Instrucciones en español; las líneas marcadas con 🗣️ **Say:** son lo que
> dices a la audiencia (en inglés — son seniors angloparlantes).

---

## 0. El workshop en una frase

Dos sesiones de 60 min (teoría ~30 / demo ~30). El eje **no** es "un refactor con IA" — es
**gobernanza lazy-proof**: cómo configurar un agente para que la calidad NO dependa del prompt
(ni de la disciplina del programador ese día). Sesión 1 demuestra la gobernanza **refactorizando**
un blog Laravel legacy; Sesión 2 la vuelve a probar haciendo **greenfield** (CRUD de Tags y
Categorías). El cierre de cada sesión: prompts para que el alumno monte esto en SU repo.

**La gran idea a transmitir:** la disciplina (tests primero, arquitectura, cobertura, ramas,
PR, obtener su propio contexto) vive en `CLAUDE.md` + `.claude/` + un hook de enforcement, no
en el prompt. Por eso el prompt que tecleas es de UNA línea y aun así el resultado es código
probado y bien estructurado. Tenemos evidencia real: el mismo prompt mínimo, tras añadir UNA
regla, pasó de 61 a 196 aserciones.

> **Modelo de dos ramas (IMPORTANTE):**
> - `mentor` → tiene TODO (estas slides, esta guía, todos los prompts). **Tu rama de trabajo.**
> - `claude-setup` → SOLO lo que el alumno debe ver: la config (`CLAUDE.md` + `.claude/`) y los
>   prompts de bootstrap/ejemplos. **Es la rama que compartes / abres frente a la audiencia.**
>
> Nunca demuestres desde `mentor` con la pantalla compartida — verían las slides y el guion.

---

## 1. Prerrequisitos del entorno

Instala/verifica ANTES del día:

- **Docker** + Docker Compose (el backend corre todo en contenedores).
- **Make** (atajos de DX: `make up`, `make test`, `make test-coverage`).
- **Claude Code** autenticado, con el repo abierto.
- **gh** (GitHub CLI) autenticado (`gh auth login`) y un remoto configurado — necesario para el
  paso final de PR.
- (Opcional, frontend) **Node 24+** y **Angular CLI** si vas a mostrar el front.

Comprobación rápida de que el stack levanta y los tests corren:

```
make up
make test            # debe salir verde
make test-coverage   # imprime cobertura (PCOV ya está en la imagen)
```

---

## 2. Mapa del repositorio

```
CLAUDE.md                         reglas durables (arquitectura, TDD/AAA, cobertura, Artisan CLI)
.claude/commands/feature.md       comando /feature: ritual red→green→refactor→branch→PR
.claude/hooks/guard-tests.php      hook: commit→verde, PR→cobertura ≥80% (corre tests en Docker)
.claude/settings.json             wiring del hook
docs/presentations/
  ├── session-0-harness-engineering.md  deck troncal conceptual (harness engineering: anillos, guides/sensors)
  ├── session-1-refactor.md        contenido slide-by-slide S1 (pásalo a Claude Code web → PPTX)
  └── session-2-greenfield.md      contenido slide-by-slide S2
docs/prompts/   (los setup-* y ejemplos están en claude-setup; los de build solo en mentor)
  ├── setup-01-claude-md.md             bootstrap: genera CLAUDE.md en el repo del alumno
  ├── setup-02-feature-command-and-hook.md  bootstrap: comando /feature + hook
  ├── 02a / 02b / 02c                   refactor Posts / Comments / Likes (ejemplos S1)
  ├── 03a / 03b                         greenfield Tags / Categorías (ejemplos S2)
  ├── 04b                               roles (bonus)
  ├── 00 / 00b / 01                     seed + tests incompletos + PCOV (build del punto 0; solo mentor)
  └── 04                                frontend Angular (solo mentor)
docs/MENTOR-GUIDE.md              este documento (solo en mentor)
```

### Ramas (clave)

```
main          limpia (para otras demos)
 └ demo-refactor   PUNTO 0: blog legacy + PCOV + suite incompleta POST-only
    └ claude-setup  punto 0 + gobierno + prompts del alumno   ← rama que ve la audiencia
       └ mentor     = claude-setup + presentaciones + esta guía + prompts de build  ← tú trabajas aquí
```

- **Demuestra siempre desde `claude-setup`** (lo que ve el alumno). `mentor` es solo para ti.
- En la demo, el `/feature` crea ramas hijas por recurso automáticamente (p. ej.
  `refactor/posts-endpoints`, `feature/tags-crud`).
- `demo-refactor` es el baseline puro sin gobierno (útil para mostrar el "antes").

---

## 3. Generar las presentaciones

`docs/presentations/*.md` es el guion slide-by-slide (título, bullets, speaker notes). Pégalo en
**Claude Code en el navegador** y pídele que genere un PowerPoint. Hazlo ANTES del día y revisa
el resultado. Las slides están en inglés; las speaker notes son tu chuleta.

---

## 4. Checklist pre-vuelo (antes de CADA sesión)

- [ ] `git checkout claude-setup` y `git pull` si aplica.
- [ ] `make up` y `make test` en verde.
- [ ] Las slides abiertas (S1 o S2).
- [ ] El archivo de prompts de la sesión abierto (`docs/prompts/...`).
- [ ] `gh auth status` OK.
- [ ] Una segunda ventana de Claude Code lista (los refactors corren prompt por prompt en
      sesiones limpias distintas).
- [ ] Zoom de la terminal grande; tema de alto contraste.

---

## 5. SESIÓN 1 — Refactor (60 min)

### Parte teórica (~30 min) — `session-1-refactor.md`

Hilo conductor (la S1 está reencuadrada: la gobernanza es el centro):

- **1–2 Title/Agenda.** 🗣️ Say: *"With most AI coding, quality tracks the prompt — which tracks
  the engineer's discipline that day. Today we remove that dependency."*
- **3 El problema.** "Prompt mejor" = meta-prompting = depende del humano. No escala.
- **4 La tesis.** Rigor en **config**, no en prompts. Prompt = QUÉ; `CLAUDE.md` = CÓMO.
- **5 Starting point.** Blog legacy inline + suite verde. 🗣️ Say: *"It works, it even has tests.
  Hold that thought."*
- **6 Refactor + la trampa.** Refactor preserva comportamiento; el suite verde solo cubre POST.
  🗣️ Say: *"Green is not safety — it's silence."*
- **7 Coverage ≠ tests exist.** Matriz (happy/422/401/404) + piso 80% backstop.
- **8 TDD + AAA.** Para legacy: characterization tests primero.
- **9 Arquitectura objetivo.** Controller→FormRequest→Service→Resource(+Policy).
- **10 La gobernanza regla por regla.** EL CENTRO. Lee cada regla y di POR QUÉ existe (varias
  nacieron de corridas reales que fallaron sin ellas).
- **11 Tres capas.** `CLAUDE.md` persuade · `/feature` = ritual · hook = enforcement.
  🗣️ Say: *"Guidance persuades; the hook guarantees."*
- **11b Why the prompt is short.** Muestra el prompt de una línea.
- **12 Evidencia.** Mismo prompt + 1 regla → 61→196 aserciones; 2 huecos hallados y blindados.
  🗣️ Say: *"Real run data, not a claim."*

### Demo (~30 min) — `session-1-refactor.md` slides 13–17

> Trabaja en **`claude-setup`** (pantalla compartida). NUNCA en `mentor`.

**Paso 1 — Tour de la gobernanza (slide 14). EL CORAZÓN.**
Abre y lee en pantalla: `CLAUDE.md` (3–4 reglas + su porqué), `.claude/commands/feature.md`,
`.claude/hooks/guard-tests.php`. 🗣️ Say: *"It's plain English and a ~30-line hook. You can build
this today."*

**Paso 2 — Exponer la trampa (slide 15).**
```
make test            # verde ✓
make test-coverage   # % bajo
```
🗣️ Say: *"A passing suite and a coverage report that disagree about safety."*

**Paso 3 — Ver la gobernanza actuar (slide 16).**
Pega el prompt de una línea de `02a-refactor-posts.md` en una sesión LIMPIA. Observa EN VIVO,
**sin que el prompt lo pida**: characterization tests primero, extracción en capas con Artisan,
aparece una `PostPolicy`, el scope se queda en Posts. Luego rompe un test e intenta `git commit`
→ el hook **bloquea**. 🗣️ Say: *"The agent does the disciplined things nobody typed — and it
cannot ship broken work."*

**Paso 4 — Bootstrap en tu repo (slide 17). EL TAKEAWAY.**
Abre `setup-01-claude-md.md` y `setup-02-feature-command-and-hook.md`. 🗣️ Say: *"Two prompts,
run in YOUR repo, reproduce this — adapted to your stack. That's what you run Monday."*

**Cierre (slides 18–19).** Recap de la tesis + teaser S2 como segunda prueba.

### Si algo falla (recuperación)
- **El stack no responde:** `make down && make up`, reintenta `make test`.
- **El hook no bloquea:** confirma que estás en `claude-setup` (tiene `.claude/settings.json`).
- **El agente se desvía:** recuérdale el prompt; el `CLAUDE.md` lo reencauza. Si se atasca,
  tienes los commits del punto 0 para reiniciar la rama (`git checkout -- .`).
- **Tentación de "arreglar" el create abierto:** úsalo como momento didáctico (es feature, no
  refactor), no lo implementes.

---

## 6. SESIÓN 2 — Greenfield (60 min)

### Parte teórica (~30 min) — `session-2-greenfield.md`, slides 1–11
- Recap S1: ahora el código está limpio y probado.
- **Greenfield vs brownfield:** el riesgo cambia — aquí los tests **definen** el contrato antes
  de que exista el código. 🗣️ Say: *"The test is the first consumer of your API. If it's
  awkward to write, the API is awkward to use."*
- **Outside-in TDD:** Feature test en el borde → el fallo va creando route/controller/request/
  service/resource. Unit tests para lógica (slug).
- **Matriz de cobertura** (happy/422/401/404) como definición de "cubierto".
- **Gobernanza recap:** mismo gate; el prompt sigue siendo corto.

### Demo (~30 min) — slides 12–16

Arranca desde el blog ya refactorizado (la última rama de la Sesión 1, p. ej.
`refactor/likes-endpoint`), para que el CRUD nuevo conviva con el código limpio. Dos prompts de
una línea, cada uno en sesión limpia (el `/feature` crea la rama):

1. Pega `03a-greenfield-tags.md` (Tags, full TDD desde cero).
2. Luego `03b-greenfield-categories.md` (Categories).

Narra el ciclo: test que falla (AAA) → mínimo para verde → validación/auth/404 → repite.
🗣️ Say: *"Same governance, now creating instead of refactoring — from one-line prompts."* La
señal estrella (slide 17b): el agente **descubre el campo `description` de Categories solo**,
leyendo la migración, sin que el prompt lo mencione. Cierra en la slide 18 apuntando otra vez a
`setup-01`/`setup-02` 🗣️ *"take it home."* Remata con `gh pr create` y el gate de cobertura.

> Recordatorio del dominio: el backend legacy NO tenía CRUD de Tags/Categorías a propósito —
> por eso son el caso greenfield perfecto.

---

## 7. Frontend Angular (opcional, fuera de las dos sesiones)

Si lo muestras: crea el workspace con `ng new` (Angular última, Tailwind, **sin SSR**) y pega
`docs/prompts/04-angular-frontend.md`. Consume la API en `http://localhost:8000/api` con token
Sanctum (Bearer). Si hay CORS desde `:4200`, publica `config/cors.php` en el backend.

---

## 8. Mensajes clave a aterrizar (si solo recuerdan 4 cosas)

1. **Un suite verde no es cobertura.** "Hay tests" es la señal más débil que existe.
2. **Refactor = preservar comportamiento.** Cambiar comportamiento es otra actividad, con su
   propio test.
3. **La disciplina va en la config, no en el prompt.** Guía (`CLAUDE.md`) persuade; el hook
   garantiza.
4. **Deja que el CLI del framework genere el esqueleto** (Artisan / `ng generate`): casa con la
   versión instalada y evita patrones obsoletos.

---

## 9. Preguntas probables de la audiencia (FAQ)

- **"¿Y si el modelo escribe tests débiles para pasar el 80%?"** Por eso la cobertura es un
  *backstop*, no la meta: la regla real es la **matriz de comportamiento** en `CLAUDE.md`.
- **"¿El hook no es lento en cada commit?"** Corre la suite en Docker; en este proyecto son
  segundos. El PR añade el coste de cobertura. Es el precio del enforcement.
- **"¿No hay roles?"** Correcto: hay autenticación y ownership (autor edita lo suyo, 403 si no),
  pero no roles. Cualquier autenticado crea posts. Es una simplificación deliberada; roles es un
  bonus con su propio TDD (`04b-roles-bonus.md`).
- **"¿Por qué Pest y no PHPUnit?"** Pest corre sobre PHPUnit; AAA se lee más limpio. La cobertura
  necesita un driver (PCOV) en cualquiera de los dos.
```
