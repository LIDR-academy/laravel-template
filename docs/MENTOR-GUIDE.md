# Guía del Mentor — Workshop "TDD con IA gobernada" (Laravel)

> **Para quién:** otro mentor que toma este repositorio y debe impartir el workshop sin
> contexto previo. Instrucciones en español; las líneas marcadas con 🗣️ **Say:** son lo que
> dices a la audiencia (en inglés — son seniors angloparlantes).

---

## 0. El workshop en una frase

Dos sesiones de 60 min (teoría ~30 / demo ~30) que enseñan **TDD con AAA** usando un agente de
IA **gobernado por configuración** (no por prompts largos). Sesión 1: **refactor** de un blog
Laravel legacy. Sesión 2: **greenfield** (CRUD nuevo de Tags y Categorías).

**La gran idea a transmitir:** la disciplina (tests primero, arquitectura, cobertura, ramas,
PR) vive en `CLAUDE.md` + `.claude/` + un hook de enforcement, no en el prompt. Por eso el
prompt que tecleas es de 3 líneas y aun así el resultado es código probado y bien estructurado.

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
  ├── session-1-refactor.md        contenido slide-by-slide S1 (pásalo a Claude Code web → PPTX)
  └── session-2-greenfield.md      contenido slide-by-slide S2
docs/prompts/
  ├── 00 / 00b / 01                seed + tests incompletos + PCOV (ya ejecutados → punto 0)
  ├── 02a / 02b / 02c              refactor Posts / Comments / Likes (Sesión 1)
  ├── 03                           greenfield Tags/Categorías (Sesión 2)
  └── 04 / 04b                     frontend Angular / roles (bonus)
docs/MENTOR-GUIDE.md              este documento
```

### Ramas (clave)

```
main          limpia (para otras demos)
 └ demo-refactor   PUNTO 0: blog legacy + PCOV + suite incompleta POST-only (3 commits)
    └ claude-setup  punto 0 + gobierno (CLAUDE.md + /feature + hook)  ← AQUÍ corre la Sesión 1
```

- **Empieza siempre la Sesión 1 desde `claude-setup`.** Crea ramas hijas por recurso:
  `refactor/posts`, `refactor/comments`, `refactor/likes`.
- `demo-refactor` es el baseline puro sin gobierno (útil si quieres mostrar el "antes").

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

### Parte teórica (~30 min) — slides 1–15

Hilo conductor por slide (qué decir / qué resaltar):

- **1–2 Title/Agenda.** 🗣️ Say: *"This isn't 'AI writes our code.' It's 'we govern an agent to
  apply disciplined engineering.' The discipline lives in config — watch how short the prompts
  get."*
- **3 Starting point.** Muestra `routes/api.php` (291 líneas, todo inline). 🗣️ Say: *"It works.
  It even has tests. Hold that thought."*
- **4 Definition.** Refactor = cambiar estructura **sin** cambiar comportamiento (Fowler).
- **5 The net.** 🗣️ Say: *"Never refactor without a net. 'We have tests' — do we, though?"*
- **6 The trap.** El golpe emocional: el suite solo cubre POST. 🗣️ Say: *"Green, but most
  behavior is unprotected. That's not safety — that's silence."*
- **7 Coverage ≠ tests exist.** Matriz de comportamiento (happy/422/401/404) + piso 80% como
  backstop. Conecta con el hook (commit→verde, PR→80%).
- **8 Characterization tests.** Capturan el comportamiento actual tal cual, en el borde HTTP.
- **9–10 TDD cycle + AAA.** Arrange/Act/Assert con comentarios explícitos.
- **11–12 Arquitectura + Before/After.** Controller→FormRequest→Service→Resource(+Policy).
- **13 Governing the agent.** El meta-mensaje. Muestra `CLAUDE.md`, `/feature` y el hook EN
  PANTALLA. 🗣️ Say: *"Guidance persuades; the hook guarantees."*
- **14 Why the prompt is short.** Abre `docs/prompts/02a-refactor-posts.md`. 🗣️ Say: *"Where's
  'write tests first' or 'hit 80%'? Not here — it's in the config."*
- **15 Strategy.** Posts → Comments → Likes, uno a la vez, suite verde tras cada extracción.

### Demo (~30 min) — slides 16–19

> Trabaja en `claude-setup`. **Solo Posts en vivo** (02a); Comments/Likes (02b/02c) muéstralos
> rápido o déjalos como follow-up — no alcanzan los 30 min para los tres con calma.

**Paso 1 — Exponer la trampa (slide 17).**
```
make test            # verde ✓
make test-coverage   # % bajo
```
🗣️ Say: *"A passing suite and a coverage report that disagree about safety. If we trusted the
green check, we'd refactor blind."* Señala en `routes/api.php` qué verbos NO están testeados.

**Paso 2 — Crear rama y lanzar el prompt (slides 14, 18).**
```
git checkout -b refactor/posts
```
Pega el prompt de `02a-refactor-posts.md` en una sesión LIMPIA de Claude Code. Narra: primero
escribe characterization tests (que pasan), luego extrae capas con Artisan (`make:controller`,
etc.). Re-corre la suite tras cada paso.

**Paso 3 — Enforcement en vivo (slide 19).**
- Introduce un fallo a propósito y deja que el agente intente `git commit` → el hook lo
  **bloquea**. 🗣️ Say: *"The agent literally cannot commit broken work."*
- Arréglalo, commit limpio, y luego `gh pr create` → corre el gate de cobertura (≥80%).

**Cierre (slides 20–21).** Recap + teaser de la Sesión 2.

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
`refactor/likes-layered-architecture`), para que el CRUD nuevo conviva con el código limpio.
Dos prompts, cada uno en sesión limpia (el `/feature` crea la rama):

1. Pega `03a-greenfield-tags.md` (Tags, full TDD desde cero).
2. Luego `03b-greenfield-categories.md` (Categories, reutiliza el patrón — ojo al campo
   `description` extra, NO es copia exacta).

Narra el ciclo: test que falla (AAA) → mínimo para verde → añade validación/auth/404 → repite.
🗣️ Say: *"Tags first, fully test-driven; Categories second, almost mechanical — that's the
payoff of a fixed architecture."* Cierra con `gh pr create` y el gate de cobertura.

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
