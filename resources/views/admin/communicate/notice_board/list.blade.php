{{-- resources/views/admin/communicate/notice_board/list.blade.php --}}
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

          @php
            
            $notices = \App\Models\Notice::query()
              ->latest()
              ->paginate(10);
          @endphp

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
                    <th class="text-end" style="width: 180px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($notices as $notice)
                    <tr>
                      <td>
                        {{ ($notices->firstItem() ?? 1) + $loop->index }}
                      </td>
                      <td>{{ $notice->title }}</td>
                      <td>{{ optional($notice->notice_date)->format('d-m-Y') }}</td>
                      <td>{{ optional($notice->publish_date)->format('d-m-Y') }}</td>
                      <td>
                            @php
                                $tos = collect(explode(',', (string) $notice->message_to))
                                        ->map(fn($v) => strtolower(trim($v)))
                                        ->filter();

                                // BS5 classes
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
                        <a href="{{ route('admin.notice-board.edit', $notice->id) }}"
                           class="btn btn-success btn-sm">Edit</a>

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
