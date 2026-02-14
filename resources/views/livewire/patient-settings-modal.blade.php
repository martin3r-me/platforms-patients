<x-ui-modal size="md" model="modalShow" header="Patient Settings">
    @if($patient)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Patient Name --}}
            @can('update', $patient)
                <x-ui-input-text 
                    name="patient.name"
                    label="Patient Name"
                    wire:model.live.debounce.500ms="patient.name"
                    placeholder="Enter patient name..."
                    required
                    :errorKey="'patient.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Patient Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $patient->name }}</span>
                </div>
            @endcan

            {{-- Description --}}
            @can('update', $patient)
                <x-ui-input-textarea 
                    name="patient.description"
                    label="Description"
                    wire:model.live.debounce.500ms="patient.description"
                    placeholder="Enter patient description..."
                    :errorKey="'patient.description'"
                />

                {{-- Company --}}
                <x-ui-input-select
                    name="selectedCompanyId"
                    label="Company (CRM)"
                    :options="$this->companyOptions"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="true"
                    nullLabel="No Company"
                    wire:model.live="selectedCompanyId"
                    placeholder="Select company..."
                    :errorKey="'selectedCompanyId'"
                />

                {{-- Contact --}}
                <x-ui-input-select
                    name="selectedContactId"
                    label="Contact Person (CRM)"
                    :options="$this->contactOptions"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="true"
                    nullLabel="No Contact Person"
                    wire:model.live="selectedContactId"
                    placeholder="Select contact person..."
                    :errorKey="'selectedContactId'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Description</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $patient->description ?? '–' }}</span>
                </div>
                @if($patient->getCompany())
                    @php
                        $company = $patient->getCompany();
                        $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
                    @endphp
                    <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                        <span class="text-[var(--ui-muted)]">Company</span>
                        <a href="{{ $companyResolver->url($company->id) }}" class="font-medium text-[var(--ui-primary)] hover:underline">
                            {{ $companyResolver->displayName($company->id) }}
                        </a>
                    </div>
                @endif
                @if($patient->getContact())
                    @php
                        $contact = $patient->getContact();
                        $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
                    @endphp
                    <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                        <span class="text-[var(--ui-muted)]">Contact Person</span>
                        <a href="{{ $contactResolver->url($contact->id) }}" class="font-medium text-[var(--ui-primary)] hover:underline">
                            {{ $contactResolver->displayName($contact->id) }}
                        </a>
                    </div>
                @endif
            @endcan
        </x-ui-form-grid>
        
        {{-- Complete Patient --}}
        @can('update', $patient)
            @if(!$patient->done)
                <div class="border-t pt-4 mt-4">
                    <x-ui-button 
                        variant="success" 
                        wire:click="markAsDone"
                        class="w-full"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span>Complete Patient</span>
                        </span>
                    </x-ui-button>
                </div>
            @else
                <div class="border-t pt-4 mt-4">
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span class="font-medium">Patient Completed</span>
                        </div>
                        @if($patient->done_at)
                            <p class="text-sm text-green-600 mt-1">
                                Completed on: {{ $patient->done_at->format('d.m.Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        @endcan
        
        {{-- Delete Patient --}}
        @can('delete', $patient)
            <div class="mt-4">
                <x-ui-confirm-button action="deletePatient" text="Delete Patient" confirmText="Really delete?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($patient)
            @can('update', $patient)
                <x-ui-button variant="success" wire:click="save">Save</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
