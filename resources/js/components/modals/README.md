# Reusable Modal Components

This directory contains reusable modal components built on top of Inertia UI modals with consistent styling, scrollbar support using tailwind-scrollbar, and proper responsive behavior.

## Components

### BaseModal

The foundational modal component that all other modals extend. Provides consistent styling, scrollbar support, and viewport-constrained dimensions.

```vue
<script setup>
import BaseModal from '@/components/modals/BaseModal.vue'

// Handlers receive close function and modalEmit from Inertia UI
const handleSave = (close, modalEmit) => {
  // Perform save operation
  console.log('Saving...')
  
  // Close modal on success
  close()
  
  // Or emit custom events
  modalEmit('saved', { data: 'example' })
}
</script>

<template>
  <BaseModal
    title="Modal Title"
    subtitle="Optional subtitle"
    max-width="2xl"
    :scrollable="true"
  >
    <!-- Your content here - has access to close and emit -->
    <template #default="{ close, emit }">
      <div class="space-y-4">
        <p>Modal content goes here</p>
        <Button @click="close">Close from Content</Button>
      </div>
    </template>
    
    <!-- Optional footer - also has access to close and emit -->
    <template #footer="{ close, emit }">
      <div class="flex justify-end gap-3">
        <Button variant="outline" @click="close">Cancel</Button>
        <Button @click="handleSave(close, emit)">Save</Button>
      </div>
    </template>
  </BaseModal>
</template>
```

#### Props
- `title?: string` - Modal title
- `subtitle?: string` - Optional subtitle
- `maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl'` - Modal width (default: '2xl')
- `position?: 'top' | 'center' | 'bottom'` - Modal position (default: 'center')
- `slideover?: boolean` - Use slideover instead of modal (default: false)
- `closeExplicitly?: boolean` - Only close via button/programmatic (default: false)
- `showCloseButton?: boolean` - Show X close button (default: true)
- `showHeader?: boolean` - Show header section (default: true)
- `scrollable?: boolean` - Enable scrollbar support (default: true)
- `contentPadding?: string` - Padding classes (default: 'p-6')

### FormModal

Specialized modal for forms with built-in submit/cancel actions and loading states.

```vue
<script setup>
import FormModal from '@/components/modals/FormModal.vue'
import { Plus } from 'lucide-vue-next'

const form = useForm({
  name: '',
  email: ''
})

// Handlers now receive close function and modalEmit from Inertia UI
const handleSubmit = (close, modalEmit) => {
  form.post('/users', {
    onSuccess: () => {
      form.reset()
      close() // Close modal on success
      toast.success('User created successfully!')
    }
  })
}

const handleCancel = (close, modalEmit) => {
  close() // Close the modal
  // Optional: navigate somewhere or emit events
}
</script>

<template>
  <FormModal
    title="Create User"
    subtitle="Add a new user to the system"
    max-width="lg"
    :is-processing="form.processing"
    submit-text="Create User"
    submit-loading-text="Creating..."
    :submit-icon="Plus"
    @submit="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- Form fields -->
    <div class="space-y-4">
      <div>
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" />
      </div>
      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" type="email" />
      </div>
    </div>
  </FormModal>
</template>
```

#### Props
- Inherits all BaseModal props plus:
- `submitText?: string` - Submit button text (default: 'Save')
- `submitLoadingText?: string` - Loading state text (default: 'Saving...')
- `cancelText?: string` - Cancel button text (default: 'Cancel')
- `submitVariant?: string` - Button variant (default: 'default')
- `submitIcon?: Component` - Icon for submit button
- `isProcessing?: boolean` - Loading state (default: false)
- `showSeparator?: boolean` - Show separator after title (default: true)
- `formActionsAlign?: 'left' | 'center' | 'right' | 'between'` - Button alignment (default: 'right')

#### Events
- `@submit(close, modalEmit)` - Emitted when submit button is clicked, receives Inertia UI close and emit functions
- `@cancel(close, modalEmit)` - Emitted when cancel button is clicked, receives Inertia UI close and emit functions

### ConfirmationModal

Modal for confirmation dialogs with different visual types and icons.

```vue
<script setup>
import ConfirmationModal from '@/components/modals/ConfirmationModal.vue'

// Handlers now receive close function and modalEmit from Inertia UI
const handleConfirm = (close, modalEmit) => {
  // Perform the destructive action
  deleteUser()
    .then(() => {
      close() // Close modal on success
      toast.success('User deleted successfully!')
    })
    .catch((error) => {
      // Handle error, modal stays open
      toast.error('Failed to delete user')
    })
}

const handleCancel = (close, modalEmit) => {
  close() // Just close the modal
}
</script>

<template>
  <ConfirmationModal
    type="danger"
    title="Delete User"
    message="Are you sure you want to delete this user? This action cannot be undone."
    confirm-text="Delete"
    cancel-text="Cancel"
    :is-processing="deleting"
    @confirm="handleConfirm"
    @cancel="handleCancel"
  >
    <!-- Optional additional content -->
    <div class="mt-3 text-sm text-gray-500">
      This will permanently delete the user and all associated data.
    </div>
  </ConfirmationModal>
</template>
```

#### Props
- `title?: string` - Confirmation title (default: 'Confirm Action')
- `message?: string` - Confirmation message
- `confirmText?: string` - Confirm button text (default: varies by type)
- `cancelText?: string` - Cancel button text (default: 'Cancel')
- `type?: 'danger' | 'warning' | 'info' | 'success'` - Visual type (default: 'danger')
- `isProcessing?: boolean` - Loading state (default: false)
- `maxWidth?: string` - Modal width (default: 'md')

#### Events
- `@confirm(close, modalEmit)` - Emitted when confirm button is clicked, receives Inertia UI close and emit functions
- `@cancel(close, modalEmit)` - Emitted when cancel button is clicked, receives Inertia UI close and emit functions

## Modal Closing & Event Communication

All modal components are built on top of Inertia UI modals and properly integrate with their closing and event system.

### Closing Modals
Every slot in our modal components receives `close` and `emit` functions from Inertia UI:

```vue
<!-- In your handlers -->
<script setup>
const handleAction = (close, modalEmit) => {
  // Do something...
  close() // Close the modal
  
  // Or emit events to parent
  modalEmit('actionComplete', { data: 'example' })
}
</script>

<!-- In templates -->
<template>
  <FormModal @submit="handleAction" @cancel="handleAction">
    <!-- Content also has access via slot props -->
    <template #default="{ close, emit }">
      <Button @click="close">Close from anywhere</Button>
    </template>
  </FormModal>
</template>
```

### Event Communication
Use `modalEmit` to communicate with the parent page:

```vue
<!-- In modal -->
<script setup>
const notifyParent = (close, modalEmit) => {
  modalEmit('dataUpdated', { newData: 'example' })
  close()
}
</script>

<!-- In parent page using ModalLink -->
<template>
  <ModalLink href="/edit-user" @data-updated="handleDataUpdated">
    Edit User
  </ModalLink>
</template>

<!-- Or with programmatic visitModal -->
<script setup>
import { visitModal } from '@inertiaui/modal-vue'

const openModal = () => {
  visitModal('/edit-user', {
    listeners: {
      dataUpdated(data) {
        console.log('Data updated:', data)
      }
    }
  })
}
</script>
```

## Features

### Scrollbar Support
All modals include tailwind-scrollbar support with proper viewport constraints:
- `max-h-[90vh]` prevents modals from overshooting the viewport
- `scrollbar-thin` provides elegant thin scrollbars
- Dark mode compatible scrollbar styling
- Fixed header/footer with scrollable content area

### Responsive Design
- Proper responsive behavior across all screen sizes
- Mobile-friendly touch interactions
- Appropriate max-widths for different content types

### Dark Mode Support
- Full dark mode compatibility
- Proper color scheme handling
- Consistent styling across light/dark themes

### Inertia UI Integration
- Built on top of Inertia UI modal components
- Leverages all Inertia UI features (stacking, prefetching, etc.)
- Zero backend configuration required

## Usage Patterns

### Opening Modals with ModalLink

```vue
<script setup>
import { ModalLink } from '@inertiaui/modal-vue'
</script>

<template>
  <ModalLink 
    href="/holidays/create"
    max-width="3xl"
    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md"
  >
    <Plus class="w-4 h-4" />
    Add Holiday
  </ModalLink>
</template>
```

### Programmatic Modal Opening

```vue
<script setup>
import { visitModal } from '@inertiaui/modal-vue'

const openCreateModal = () => {
  visitModal('/holidays/create', {
    config: {
      maxWidth: '3xl',
      scrollable: true
    }
  })
}
</script>
```

### Form Validation Integration

```vue
<FormModal
  title="Create Holiday"
  :is-processing="form.processing"
  @submit="handleSubmit"
  @cancel="handleCancel"
>
  <div class="space-y-4">
    <div>
      <Label for="name">Name *</Label>
      <Input 
        id="name" 
        v-model="form.name" 
        :class="{ 'border-destructive': form.errors.name }"
        required 
      />
      <InputError :message="form.errors.name" />
    </div>
  </div>
</FormModal>
```

## Customization

### Custom Actions
Override the default actions in FormModal:

```vue
<FormModal title="Custom Actions">
  <!-- content -->
  
  <template #actions>
    <Button variant="outline">Custom Cancel</Button>
    <Button variant="secondary">Save Draft</Button>
    <Button>Publish</Button>
  </template>
</FormModal>
```

### Custom Header
Override the default header in BaseModal:

```vue
<BaseModal>
  <template #header>
    <div class="flex items-center gap-3">
      <AlertTriangle class="w-6 h-6 text-amber-500" />
      <div>
        <h2 class="text-lg font-semibold">Custom Header</h2>
        <p class="text-sm text-muted-foreground">With icon and description</p>
      </div>
    </div>
  </template>
  
  <!-- content -->
</BaseModal>
```