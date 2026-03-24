<?php

declare(strict_types=1);

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$totalChance = 0;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slim Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-slate-950 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.18),transparent_30%),linear-gradient(180deg,#020617_0%,#0f172a_45%,#111827_100%)] px-4 py-10 text-slate-100">
    <main>
      <section class="rounded-4xl border border-white/10 bg-white/95 p-6 text-slate-900 shadow-2xl shadow-slate-950/30 sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-sky-600">Authenticated Segment Editor</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Wheel Rows With Dynamic Inputs</h1>
        <div class="mt-6 space-y-4">
          <?php if ($errors !== []): ?>
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              <ul class="list-disc space-y-1 pl-5">
                <?php foreach ($errors as $error): ?>
                  <li><?= $escape($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if ($successMessage !== null): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              <?= $escape($successMessage) ?>
            </div>
          <?php endif; ?>
        </div>
        <form method="post" class="mt-6 space-y-5" id="segments-form">
          <div class="space-y-4" id="rows-container">
            <?php foreach ($formRows as $row): ?>
              <?php
              $name = $escape($row['name']);
              $color = $escape($row['color']);
              $chance = $escape($row['chance']);
              $totalChance += (int) $row['chance'];
              ?>
              <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1.45fr_0.85fr_0.75fr_auto]">
                <label class="block space-y-2">
                  <span class="text-sm font-semibold text-slate-700">Name</span>
                  <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" type="text" name="segment_name[]" data-field="name" value="<?= $name ?>">
                </label>
                <label class="block space-y-2">
                  <span class="text-sm font-semibold text-slate-700">Color</span>
                  <input class="h-[52px] w-full rounded-xl border border-slate-300 bg-white px-2 py-2 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" type="color" name="segment_color[]" data-field="color" value="<?= $color ?>">
                </label>
                <label class="block space-y-2">
                  <span class="text-sm font-semibold text-slate-700">Chance</span>
                  <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200" type="number" min="0" max="100" step="1" name="segment_chance[]" data-field="chance" value="<?= $chance ?>">
                </label>
                <div class="flex items-end">
                  <button class="w-full rounded-xl border border-rose-200 px-4 py-3 text-sm font-bold uppercase tracking-[0.14em] text-rose-600 transition hover:border-rose-300 hover:bg-rose-50" type="button" data-remove-row>
                    Remove
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-slate-950 px-5 py-4 text-white">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-300">Current Total</p>
              <p class="mt-1 text-2xl font-black tracking-tight"><span id="chance-total"><?= $totalChance ?></span> / 100</p>
            </div>
            <div class="flex flex-wrap gap-3">
              <button class="inline-flex items-center justify-center rounded-full border border-slate-700 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-slate-200 transition hover:border-slate-500 hover:bg-slate-900" type="button" id="add-row-button">
                Add Row
              </button>
              <button class="inline-flex items-center justify-center rounded-full bg-sky-500 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-sky-400" type="submit" id="save-button">
                Save
              </button>
            </div>
          </div>
        </form>
      </section>
    </main>
    
    <script>
      const rowsContainer = document.getElementById('rows-container');
      const rowTemplate = document.getElementById('segment-row-template');
      const chanceTotal = document.getElementById('chance-total');
      const form = document.getElementById('segments-form');
      const addRowButton = document.getElementById('add-row-button');

      function updateTotal() {
        const total = Array.from(rowsContainer.querySelectorAll('[data-field="chance"]')).reduce((sum, input) => {
          return sum + (parseInt(input.value || '0', 10) || 0);
        }, 0);

        chanceTotal.textContent = String(total);
        return total;
      }

      function addRow() {
        rowsContainer.appendChild(rowTemplate.content.cloneNode(true));
        updateTotal();
      }

      function removeRow(button) {
        const row = button.closest('.grid');
        if (!row) {
          return;
        }

        row.remove();

        if (rowsContainer.children.length === 0) {
          addRow();
          return;
        }

        updateTotal();
      }

      updateTotal();

      form.addEventListener('input', (event) => {
        if (event.target.matches('[data-field="chance"]')) {
          updateTotal();
        }
      });

      addRowButton.addEventListener('click', () => {
        addRow();
      });

      form.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove-row]')) {
          removeRow(event.target);
        }
      });

      form.addEventListener('submit', (event) => {
        if (updateTotal() !== 100) {
          event.preventDefault();
          window.alert('The sum of all chance fields must be exactly 100 before saving.');
        }
      });
    </script>
  </body>
</html>
