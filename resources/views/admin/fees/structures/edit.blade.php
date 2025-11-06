@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  {{-- Header --}}
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col">
          <h3 class="mb-0">Edit Fee Structure</h3>
        </div>
        <div class="col text-end">
          <a href="{{ route('admin.fees.structures.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Body --}}
  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <form action="{{ route('admin.fees.structures.update', $structure->id) }}" method="post" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="row g-3">
          {{-- Base tuition --}}
          <div class="col-12 col-xl-6">
            <div class="card card-warning card-outline h-100">
              <div class="card-header"><strong>Base Tuition</strong></div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Class <span class="text-danger">*</span></label>
                  <select name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                    <option value="">-- Select class --</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ old('class_id', $structure->class_id)==$c->id?'selected':'' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                  <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                  <input type="text" name="academic_year"
                         value="{{ old('academic_year', $structure->academic_year) }}"
                         class="form-control @error('academic_year') is-invalid @enderror" required>
                  @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label">Annual Fee (base) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="annual_fee" id="annual_fee"
                           value="{{ old('annual_fee', $annualFee) }}"
                           class="form-control @error('annual_fee') is-invalid @enderror" required>
                    @error('annual_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Monthly (auto)</label>
                    <input type="text" id="monthly_preview" class="form-control" readonly>
                    <div class="form-text">Calculated as: Annual / 12</div>
                  </div>
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from"
                           value="{{ old('effective_from', $structure->effective_from) }}"
                           class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to"
                           value="{{ old('effective_to', $structure->effective_to) }}"
                           class="form-control">
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Components --}}
          <div class="col-12 col-xl-6">
            <div class="card card-warning card-outline h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Additional Components</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponentRow()">
                  <i class="bi bi-plus-lg"></i> Add Component
                </button>
              </div>
              <div class="card-body">
                <div id="component-rows"></div>
                <div class="text-muted small">
                  <ul class="mb-0">
                    <li><strong>Include in monthly</strong>: Transport/Hostel ইত্যাদি মাসিক বিলে যোগ হবে।</li>
                    <li><strong>Bill month</strong>: নির্দিষ্ট মাস নির্বাচন করলে শুধু ঐ মাসের ইনভয়েসে বেস টিউশনের সাথে এই কম্পোনেন্ট যোগ হবে।</li>
                    <li><strong>Calc Type</strong>: প্রয়োজনে ডিফল্ট calc টাইপ override করুন।</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {{-- Submit --}}
          <div class="col-12">
            <div class="d-flex gap-2">
              <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Update</button>
              <a href="{{ route('admin.fees.structures.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </div>
      </form>

    </div>
  </div>
</main>
@endsection

@push('scripts')
@php
  $componentsForJs = $components->map(function ($c) {
      return [
          'id' => $c->id,
          'name' => $c->name,
          'frequency' => $c->frequency,
          'calc_type' => $c->calc_type,
      ];
  })->values();

  $termsForJs = ($terms ?? collect())->map(function ($t) {
      return [
          'id' => $t->id,
          'name' => $t->name,
          'academic_year' => $t->academic_year,
      ];
  })->values();

  $prefillRows = $structure->components->map(function ($c) {
      return [
          'component_id'       => $c->id,
          'calc_type'          => $c->pivot->calc_type_override,
          'include_in_monthly' => (bool) $c->pivot->include_in_monthly,
          'bill_month'         => $c->pivot->bill_month,
          'fee_term_id'        => $c->pivot->fee_term_id,
      ];
  })->values();
@endphp

<script>
  // ---- Monthly preview
  function refreshMonthly(){
    const annual = parseFloat(document.getElementById('annual_fee')?.value || '0');
    const m = isFinite(annual) ? (annual/12) : 0;
    const el = document.getElementById('monthly_preview');
    if (el) el.value = m.toFixed(2);
  }
  document.getElementById('annual_fee')?.addEventListener('input', refreshMonthly);
  refreshMonthly();

  // ---- Data
  const COMPONENTS = @json($componentsForJs);
  const TERMS = @json($termsForJs);
  const PREFILL_ROWS = @json($prefillRows);

  function componentOptionsHtml(selectedId=null){
    let html = '<option value="">-- Select component --</option>';
    (COMPONENTS||[]).forEach(function(c){
      const sel = (selectedId && Number(selectedId)===Number(c.id)) ? 'selected' : '';
      html += `<option value="${c.id}" ${sel}>${c.name} (${c.frequency})</option>`;
    });
    return html;
  }

  function termOptionsHtml(selectedId=null){
    let html = '<option value="">-- No term --</option>';
    (TERMS||[]).forEach(function(t){
      const sel = (selectedId && Number(selectedId)===Number(t.id)) ? 'selected' : '';
      html += `<option value="${t.id}" ${sel}>${t.academic_year} — ${t.name}</option>`;
    });
    return html;
  }

  function monthOptionsHtml(selected=null){
    const months = [
      [1,'January'],[2,'February'],[3,'March'],[4,'April'],[5,'May'],[6,'June'],
      [7,'July'],[8,'August'],[9,'September'],[10,'October'],[11,'November'],[12,'December']
    ];
    let html = '<option value="">-- Select month --</option>';
    months.forEach(([val,label])=>{
      const sel = (selected && Number(selected)===Number(val)) ? 'selected' : '';
      html += `<option value="${val}" ${sel}>${label}</option>`;
    });
    return html;
  }

  function addComponentRow(prefill=null){
    const container = document.getElementById('component-rows');
    const idx = container.querySelectorAll('.comp-row').length;

    const selectedId     = prefill && prefill.component_id       ? prefill.component_id       : '';
    const calcType       = prefill && prefill.calc_type          ? prefill.calc_type          : '';
    const includeMonthly = prefill && prefill.include_in_monthly ? 'checked' : '';
    const billMonth      = prefill && prefill.bill_month         ? prefill.bill_month         : '';
    const termId         = prefill && prefill.fee_term_id        ? prefill.fee_term_id        : '';

    const row = document.createElement('div');
    row.className = 'comp-row border rounded p-2 mb-2';

    row.innerHTML = `
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Component</label>
          <select name="components[${idx}][component_id]" class="form-select" required>
            ${componentOptionsHtml(selectedId)}
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Calc Type</label>
          <select name="components[${idx}][calc_type]" class="form-select">
            <option value="">Default</option>
            <option value="fixed" ${calcType==='fixed'?'selected':''}>Fixed</option>
            <option value="percent_of_base" ${calcType==='percent_of_base'?'selected':''}>% of base</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Term (optional)</label>
          <select name="components[${idx}][fee_term_id]" class="form-select">
            ${termOptionsHtml(termId)}
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Bill month</label>
          <select name="components[${idx}][bill_month]" class="form-select">
            ${monthOptionsHtml(billMonth)}
          </select>
        </div>
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input type="hidden" name="components[${idx}][include_in_monthly]" value="0">
            <input type="checkbox" class="form-check-input" id="incm_${idx}" name="components[${idx}][include_in_monthly]" value="1" ${includeMonthly}>
            <label class="form-check-label" for="incm_${idx}">Include in monthly</label>
          </div>
        </div>
      </div>
      <div class="text-end mt-2">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.comp-row').remove()">
          Remove
        </button>
      </div>
    `;
    container.appendChild(row);
  }

  // Prefill existing rows
  (PREFILL_ROWS || []).forEach(function(p){ addComponentRow(p); });
</script>
@endpush
