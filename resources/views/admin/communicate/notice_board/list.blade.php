@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $header_title ?? 'Notice Board' }}</h3>
        </div>
        <div class="col-sm-6">
          <a href="{{ route('admin.notice-board.add') }}" class="btn btn-primary float-sm-end">Add Notice</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- =========================
               Filters
               ========================= --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header">
              <h3 class="card-title">Filter Notices</h3>
            </div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.notice-board.list') }}" class="row g-3 align-items-end">

                <div class="col-md-4">
                  <label class="form-label">Search</label>
                  <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                         placeholder="Title or message…">
                </div>

                <div class="col-md-3">
                  <label class="form-label">From (Publish Date)</label>
                  <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>

                <div class="col-md-3">
                  <label class="form-label">To (Publish Date)</label>
                  <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>

                <div class="col-md-2">
                  <label class="form-label">Per Page</label>
                  <select name="per_page" class="form-select">
                    @foreach([10,20,50,100] as $n)
                      <option value="{{ $n }}" @selected((int)request('per_page', 10) === $n)>{{ $n }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Apply
                  </button>
                  <a href="{{ route('admin.notice-board.list') }}" class="btn btn-outline-secondary">
                    Reset
                  </a>
                </div>
              </form>
            </div>
          </div>

          {{-- =========================
               Notice list
               ========================= --}}
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Notice List</h3>
            </div>

            <div class="card-body p-0">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th style="width: 70px;">Serial</th>
                    <th>Title</th>
                    <th>Notice Date</th>
                    <th>Publish Date</th>
                    <th>Message To</th>
                    <th>Created Date</th>
                    <th class="text-end" style="width: 230px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($notices as $notice)
                    <tr>
                      <td>{{ ($notices->firstItem() ?? 1) + $loop->index }}</td>

                      <td class="text-wrap">{{ $notice->title }}</td>

                      <td>
                        @if($notice->notice_date)
                          {{ \Illuminate\Support\Carbon::parse($notice->notice_date)->format('d-m-Y') }}
                        @else
                          —
                        @endif
                      </td>

                      <td>
                        @if($notice->publish_date)
                          {{ \Illuminate\Support\Carbon::parse($notice->publish_date)->format('d-m-Y') }}
                        @else
                          —
                        @endif
                      </td>

                      <td>
                        @php
                          $tos = collect(explode(',', (string) $notice->message_to))
                                  ->map(fn($v) => strtolower(trim($v)))
                                  ->filter();

                          $map = [
                            'student' => 'bg-primary',
                            'teacher' => 'bg-success',
                            'parent'  => 'bg-info',
                          ];
                        @endphp
                        @forelse ($tos as $to)
                          <span class="badge {{ $map[$to] ?? 'bg-secondary' }} rounded-pill me-1">
                            {{ ucfirst($to) }}
                          </span>
                        @empty
                          <span class="text-muted">—</span>
                        @endforelse
                      </td>

                      <td>{{ optional($notice->created_at)->format('d-m-Y') }}</td>

                      <td class="text-end">
                        {{-- Download PDF button --}}
                        <a href="{{ route('admin.notice-board.download', ['id' => $notice->id, 'slug' => \Illuminate\Support\Str::slug($notice->title)]) }}"
                           target="_blank" rel="noopener"
                           class="btn btn-outline-danger btn-sm" title="Download PDF">
                          <i class="bi bi-file-earmark-pdf-fill"></i>
                        </a>
                        
                        <a href="{{ route('admin.notice-board.edit', $notice->id) }}"
                           class="btn btn-success btn-sm">
                          Edit
                        </a>

                        <form action="{{ route('admin.notice-board.destroy', $notice->id) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this notice?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center text-muted p-4">No notices found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>

              @if(method_exists($notices, 'links'))
                <div class="p-3">
                  {{ $notices->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
              @endif
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
