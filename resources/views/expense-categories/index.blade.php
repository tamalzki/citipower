@extends('layouts.app')

@section('title', 'Expense Categories')

@section('content')
    <div class="page-header">
        <div>
            <h2>Expense Categories</h2>
            <p>Manage categories like Utilities, Food, and other expense groups</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Expenses</a>
            <a href="{{ route('expense-categories.create') }}" class="btn btn-primary">+ Add Category</a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Used In</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td style="font-weight:600; color:#0f172a;">{{ $category->name }}</td>
                            <td>{{ $category->description ?: '—' }}</td>
                            <td>
                                <span class="badge badge-info">{{ number_format($category->expenses_count) }} expense(s)</span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('expense-categories.edit', $category) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    <form action="{{ route('expense-categories.destroy', $category) }}" method="POST"
                                          class="offline-ec-delete-form" data-category-id="{{ $category->id }}"
                                          style="display:inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">🏷️</div>
                                    <p>No expense categories yet.</p>
                                    <a href="{{ route('expense-categories.create') }}" class="btn btn-primary">Add Category</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <script>
        (function () {
            document.querySelectorAll('.offline-ec-delete-form').forEach(function (form) {
                form.addEventListener('submit', async function (e) {
                    if (navigator.onLine || !window.CitiOffline?.queueExpenseCategoryDelete) return;
                    e.preventDefault();
                    if (!confirm('Delete this category?')) return;
                    const cid = parseInt(form.dataset.categoryId || '0', 10);
                    try {
                        const ref = await window.CitiOffline.queueExpenseCategoryDelete({ expense_category_id: cid });
                        alert('Offline: Delete queued. Ref: ' + ref.slice(0, 8));
                        form.closest('tr')?.remove();
                    } catch (err) { alert((err && err.message) || 'Queue failed.'); }
                });
            });
        })();
    </script>
@endsection
