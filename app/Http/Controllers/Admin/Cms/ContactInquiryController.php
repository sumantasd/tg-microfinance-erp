<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\Request;

class ContactInquiryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = ContactInquiry::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $inquiries = $query->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.contact.index', compact('inquiries'));
    }

    public function show(ContactInquiry $contact)
    {
        $this->authorize('website.manage');

        if ($contact->status === 'unread') {
            $contact->update(['status' => 'read']);
        }

        return view('admin.cms.contact.show', ['inquiry' => $contact]);
    }

    public function toggleStatus(ContactInquiry $contact)
    {
        $this->authorize('website.manage');

        $newStatus = $contact->status === 'read' ? 'unread' : 'read';
        $contact->update(['status' => $newStatus]);

        return back()->with('success', 'Inquiry marked as ' . strtoupper($newStatus));
    }

    public function destroy(ContactInquiry $contact)
    {
        $this->authorize('website.manage');

        $contact->delete();

        return redirect()->route('admin.cms.contact.index')->with('success', 'Contact inquiry deleted successfully.');
    }
}
