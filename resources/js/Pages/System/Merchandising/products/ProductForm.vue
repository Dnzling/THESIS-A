<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Loading Skeleton -->
    <div v-if="loadingData" class="space-y-6">
      <Skeleton height="60px" class="rounded-2xl" />
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <Skeleton height="400px" class="rounded-2xl" />
          <Skeleton height="300px" class="rounded-2xl" />
        </div>
        <div class="lg:col-span-1">
          <Skeleton height="600px" class="rounded-2xl" />
        </div>
      </div>
    </div>
  
    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit">
      <!-- iOS-style Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button type="button" @click="router.push({ name: 'merchandising.products' })"
            class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200">
            <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
          </button>
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
              {{ isEditMode ? 'Edit Product' : 'Create Product' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ isEditMode ? 'Update product information' : 'Add a new product to your catalog' }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button type="submit" :label="isEditMode ? 'Update Product' : 'Create Product'" icon="pi pi-check"
            :loading="submitting" class="rounded-xl px-5 py-2.5 bg-blue-500 hover:bg-blue-600 border-none text-white" />
        </div>
      </div>
  
      <!-- 1-page Layout (Form + Live Preview) -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-6 items-start">
        <!-- Form (left) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="p-6 space-y-10">
            <!-- Product Information -->
            <section class="space-y-6">
              <div class="pb-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Product Information</h2>
                <p class="text-sm text-gray-500 mt-1">Basic details about your product</p>
              </div>
              <!-- Product Name -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Product Name <span class="text-red-500">*</span>
                </label>
                <InputText v-model="form.product_name" placeholder="e.g., Modern L-Shaped Sectional Sofa"
                  :class="{ 'p-invalid': errors.product_name }" @input="generateSKU"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
              </div>
  
              <!-- SKU -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  SKU <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                  <InputText v-model="form.sku" placeholder="Will be auto-generated" :class="{ 'p-invalid': errors.sku }"
                    readonly class="flex-1 bg-gray-100 border-gray-200 rounded-xl" />
                  <Button icon="pi pi-copy" v-tooltip.top="'Copy SKU'" severity="secondary" outlined @click="copySKU"
                    :disabled="!form.sku" class="rounded-xl" />
                  <Button icon="pi pi-refresh" v-tooltip.top="'Regenerate SKU'" severity="secondary" outlined
                    @click="generateSKU" :disabled="!form.product_name || !form.category_id" class="rounded-xl" />
                </div>
                <small class="text-gray-500 text-xs">Format: CATEGORY-ATTRIBUTE-001</small>
                <small v-if="errors.sku" class="text-red-500">{{ errors.sku }}</small>
              </div>
  
              <!-- Category, Subcategory & Unit -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">
                    Category <span class="text-red-500">*</span>
                  </label>
                  <Select v-model="form.category_id" :options="categories" optionLabel="category_name" optionValue="id"
                    placeholder="Select a category" :class="{ 'p-invalid': errors.category_id }"
                    :loading="loadingCategories" @change="onCategoryChange"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                  <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
                </div>
  
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Subcategory</label>
                  <Select v-model="form.subcategory_id" :options="subcategories" optionLabel="category_name"
                    optionValue="id" placeholder="Select subcategory" showClear :disabled="!form.category_id"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Unit</label>
                  <Select v-model="form.unit_code" :options="unitOptions" optionLabel="label" optionValue="value"
                    placeholder="Select unit" showClear
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                </div>
              </div>
  
              <!-- Product Type -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Product Type <span class="text-red-500">*</span>
                </label>
                <Select
                  v-model="form.product_type"
                  :options="productTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select product type"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl"
                />
              </div>
  
              <!-- Brand & Collection -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Brand</label>
                  <InputText v-model="form.brand" placeholder="e.g., IKEA, Ashley Furniture" @input="generateSKU"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Collection Name</label>
                  <InputText v-model="form.collection_name" placeholder="e.g., Summer 2024"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                </div>
              </div>
  
              <!-- Description -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Description</label>
                <Editor v-model="form.description" editorStyle="height: 320px"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl overflow-hidden" />
                <small class="text-gray-500 text-xs">You can format text (bold, italic, lists, links).</small>
              </div>
  
              <!-- Dimensions -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500">Length (cm)</label>
                  <InputNumber v-model="form.length_cm" :minFractionDigits="2" suffix=" cm" :min="0"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                </div>
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500">Width (cm)</label>
                  <InputNumber v-model="form.width_cm" :minFractionDigits="2" suffix=" cm" :min="0"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                </div>
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500">Height (cm)</label>
                  <InputNumber v-model="form.height_cm" :minFractionDigits="2" suffix=" cm" :min="0"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                </div>
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-500">Weight (kg)</label>
                  <InputNumber v-model="form.weight_kg" :minFractionDigits="2" suffix=" kg" :min="0"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                </div>
              </div>
  
              <!-- Flags -->
              <div class="flex flex-wrap gap-4 pt-2">
                <div v-if="!isRawMaterialType" class="flex items-center gap-2">
                  <Checkbox v-model="form.is_featured" inputId="featured" :binary="true" />
                  <label for="featured" class="text-sm text-gray-700 cursor-pointer">Featured</label>
                </div>
                <div v-if="!isRawMaterialType" class="flex items-center gap-2">
                  <Checkbox v-model="form.is_new_arrival" inputId="newArrival" :binary="true" />
                  <label for="newArrival" class="text-sm text-gray-700 cursor-pointer">New Arrival</label>
                </div>
                <div v-if="!isRawMaterialType" class="flex items-center gap-2">
                  <Checkbox v-model="form.is_bestseller" inputId="bestseller" :binary="true" />
                  <label for="bestseller" class="text-sm text-gray-700 cursor-pointer">Bestseller</label>
                </div>
                <div class="flex items-center gap-2">
                  <Checkbox v-model="form.assembly_required" inputId="assembly" :binary="true" />
                  <label for="assembly" class="text-sm text-gray-700 cursor-pointer">Assembly Required</label>
                </div>
                <div class="flex items-center gap-2">
                  <Checkbox v-model="form.is_active" inputId="active" :binary="true" />
                  <label for="active" class="text-sm text-gray-700 cursor-pointer">Active</label>
                </div>
              </div>
            </section>

            <!-- Pricing -->
            <section class="space-y-6">
              <div class="pb-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Pricing</h2>
                <p class="text-sm text-gray-500 mt-1">Set the price and tax information</p>
              </div>
              <Message v-if="isEditMode && form.price_approval_status === 'pending'" severity="warn" :closable="false">
                Price update is pending finance approval. Live selling price will stay unchanged until approved.
              </Message>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Base Price <span class="text-red-500">*</span></label>
                  <InputNumber id="base_price" v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH"
                    :class="{ 'p-invalid': errors.base_price }" :min="0"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                  <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
                </div>
  
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Cost Price</label>
                  <InputNumber id="cost_price" v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH"
                    :min="0" disabled class="w-full bg-gray-100 border-gray-200 rounded-xl" fluid />
                  <small class="text-gray-500 text-xs">Auto-managed value (read-only)</small>
                </div>
  
                <div v-if="!isRawMaterialType" class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Discounted Price</label>
                  <InputNumber id="discounted_price" v-model="form.discounted_price" mode="currency" currency="PHP"
                    locale="en-PH" :min="0" class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                  <small class="text-gray-500 text-xs">Leave empty if no discount</small>
                </div>
  
                <div class="space-y-2">
                  <label class="text-sm font-medium text-gray-700">Tax Rate (%)</label>
                  <InputNumber id="tax_rate" v-model="form.tax_rate" suffix="%" :min="0" :max="100" :minFractionDigits="2"
                    disabled class="w-full bg-gray-100 border-gray-200 rounded-xl" fluid />
                  <small class="text-gray-500 text-xs">Auto-managed value (read-only)</small>
                </div>
              </div>

              <div v-if="form.base_price || form.discounted_price" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Display Price Preview</p>
                <p v-if="form.discounted_price" class="mt-2 text-3xl font-semibold text-red-600">
                  {{ formatCurrencyPHP(form.discounted_price) }}
                </p>
                <p :class="form.discounted_price ? 'text-xl font-medium text-gray-500 line-through' : 'mt-2 text-3xl font-semibold text-gray-900'">
                  {{ formatCurrencyPHP(form.base_price || 0) }}
                </p>
              </div>
  
              <!-- Price Change Reason -->
              <div v-if="isEditMode && originalBasePrice !== form.base_price" class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Price Change Reason <span class="text-red-500">*</span>
                </label>
                <InputText id="price_change_reason" v-model="form.price_change_reason"
                  placeholder="e.g., Seasonal discount, Supplier cost increase"
                  :class="{ 'p-invalid': errors.price_change_reason }"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                <small v-if="errors.price_change_reason" class="text-red-500">{{ errors.price_change_reason }}</small>
              </div>
            </section>
  
            <!-- Assets -->
            <section class="space-y-6">
              <div class="pb-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Assets</h2>
                <p class="text-sm text-gray-500 mt-1">Upload 3D models and product images</p>
              </div>
              <!-- 3D Model Upload -->
              <div class="space-y-3">
                <label class="text-sm font-medium text-gray-700">3D Model</label>
  
                <!-- Upload Area -->
                <div v-if="!form.modelFile && !existingModel" @click="modelInput?.click()" @dragover.prevent
                  @drop.prevent="handleModelDrop"
                  class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 hover:bg-blue-50/30 transition-all cursor-pointer">
                  <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-3 block"></i>
                  <p class="text-sm font-medium text-gray-700 mb-1">Drop 3D model here</p>
                  <p class="text-xs text-gray-500">or click to browse</p>
                  <p class="text-xs text-gray-400 mt-2">Supported: GLB, GLTF (Max 100MB)</p>
                </div>
                <input ref="modelInput" type="file" accept=".glb,.gltf" class="hidden" @change="handleModelSelect" />
  
                <!-- New Model Preview -->
                <div v-if="form.modelFile" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="pi pi-cube text-blue-600"></i>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-900">{{ form.modelFile.name }}</p>
                        <p class="text-xs text-gray-600">{{ formatFileSize(form.modelFile.size) }}</p>
                      </div>
                    </div>
                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeModel" />
                  </div>
                  <div class="mt-2 flex items-center gap-2 text-xs text-green-700">
                    <i class="pi pi-check-circle"></i>
                    <span>Ready to upload</span>
                  </div>
                </div>
  
                <!-- Existing Model Preview -->
                <div v-if="existingModel && !form.modelFile" class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="pi pi-cube text-gray-600"></i>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-900">{{ existingModel.file_name }}</p>
                        <p class="text-xs text-gray-600">{{ formatFileSize(existingModel.file_size_kb * 1024) }}</p>
                      </div>
                    </div>
                    <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteExistingModel" />
                  </div>
                  <Tag value="Existing Model" severity="info" class="mt-2 w-full justify-center" />
                </div>
  
                <div v-if="existingModelPreviewUrl" class="space-y-2">
                  <p class="text-xs font-medium text-gray-500">3D Preview</p>
                  <Model3DPreview :model-url="existingModelPreviewUrl" :model-format="existingModel?.model_format"
                    :auth-token="previewAuthToken" :camera-x="form.default_camera_angle_x"
                    :camera-y="form.default_camera_angle_y" :zoom="form.default_zoom_level" height="280px" />
                </div>
              </div>
  
              <!-- Camera Settings -->
              <div class="space-y-3 pt-4 border-t border-gray-100">
                <h4 class="text-sm font-semibold text-gray-900">Camera Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <label class="text-xs font-medium text-gray-500">Angle X (deg)</label>
                    <InputNumber v-model="form.default_camera_angle_x" :min="-180" :max="180" suffix="°" showButtons
                      buttonLayout="horizontal" :step="5" class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                  </div>
                  <div class="space-y-2">
                    <label class="text-xs font-medium text-gray-500">Angle Y (deg)</label>
                    <InputNumber v-model="form.default_camera_angle_y" :min="-180" :max="180" suffix="°" showButtons
                      buttonLayout="horizontal" :step="5" class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                  </div>
                  <div class="space-y-2">
                    <label class="text-xs font-medium text-gray-500">Zoom Level</label>
                    <InputNumber v-model="form.default_zoom_level" :min="0.1" :max="20" :minFractionDigits="1" showButtons
                      buttonLayout="horizontal" :step="0.1" class="w-full bg-gray-50 border-gray-200 rounded-xl" fluid />
                  </div>
                </div>
              </div>
  
              <!-- Images Upload -->
              <div class="space-y-3 pt-4 border-t border-gray-100">
                <h4 class="text-sm font-semibold text-gray-900">Product Images</h4>
                <div class="space-y-2">
                  <FileUpload
                    v-if="remainingImageSlots > 0"
                    mode="basic"
                    name="images[]"
                    accept="image/*"
                    :maxFileSize="5000000"
                    :multiple="true"
                    :auto="false"
                    :chooseLabel="`Upload Images (${remainingImageSlots} left)`"
                    class="w-full"
                    @select="handleImageSelect"
                  />
                  <Message v-else severity="info" :closable="false">
                    You’ve reached the maximum of 4 images. Remove one to upload more.
                  </Message>
                  <small class="text-gray-500 text-xs">Up to 4 images. JPG, PNG, WebP (Max 5MB each). Images are cropped to square.</small>
                </div>

                <!-- PrimeVue Galleria Preview -->
                <div v-if="previewGalleryItems.length" class="mt-2">
                  <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50">
                    <div class="relative aspect-square">
                      <img
                        v-if="selectedAssetPreviewItem?.src"
                        :src="selectedAssetPreviewItem.src"
                        :alt="selectedAssetPreviewItem.alt"
                        class="absolute inset-0 w-full h-full object-cover"
                      />
                      <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400">
                        <i class="pi pi-image text-3xl"></i>
                      </div>
                      <div class="absolute top-3 left-3 flex gap-2">
                        <Tag v-if="selectedAssetPreviewItem?.isPrimary" value="Primary" severity="success" />
                        <Tag v-if="selectedAssetPreviewItem?.type === 'existing'" value="Existing" severity="secondary" />
                        <Tag v-else value="New" severity="info" />
                      </div>
                      <Button
                        v-if="selectedAssetPreviewItem?.type === 'existing'"
                        icon="pi pi-trash"
                        severity="danger"
                        rounded
                        size="small"
                        class="absolute top-3 right-3"
                        @click="selectedAssetPreviewItem?.raw && deleteExistingImage(selectedAssetPreviewItem.raw)"
                      />
                      <Button
                        v-else
                        icon="pi pi-times"
                        severity="danger"
                        rounded
                        size="small"
                        class="absolute top-3 right-3"
                        @click="typeof selectedAssetPreviewItem?.index === 'number' && removeImage(selectedAssetPreviewItem.index)"
                      />
                    </div>
                  </div>

                  <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                    <button
                      v-for="(it, idx) in previewGalleryItems"
                      :key="it.key"
                      type="button"
                      class="shrink-0 w-14 h-14 rounded-xl overflow-hidden border transition-colors"
                      :class="idx === selectedAssetPreviewIndex ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'"
                      @click="selectedAssetPreviewIndex = idx"
                    >
                      <img :src="it.src" :alt="it.alt" class="w-full h-full object-cover" />
                    </button>
                  </div>
                </div>
              </div>
            </section>

            <!-- Variations -->
            <section class="space-y-6">
              <div class="pb-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Variations</h2>
                <p class="text-sm text-gray-500 mt-1">Create and manage this product variants</p>
              </div>
              <Message v-if="!isEditMode" severity="info" :closable="false">
                Save this product first, then you can create and manage variations here.
              </Message>

              <template v-else>
                <div class="flex items-center justify-between">
                  <p class="text-sm text-gray-500">Manage this product variations in one place.</p>
                  <Button label="Add Variation" icon="pi pi-plus" class="rounded-xl" @click="openCreateVariationDialog" />
                </div>

                <DataTable
                  :value="variations"
                  :loading="loadingVariations"
                  dataKey="id"
                  stripedRows
                  class="p-datatable-sm rounded-xl overflow-hidden border border-gray-100"
                >
                  <template #empty>
                    <div class="py-8 text-center text-sm text-gray-500">No variations yet.</div>
                  </template>

                  <Column field="variation_sku" header="SKU" style="min-width: 180px">
                    <template #body="{ data }">
                      <span class="font-mono text-xs font-semibold">{{ data.variation_sku || '-' }}</span>
                    </template>
                  </Column>
                  <Column field="variation_name" header="Name" style="min-width: 220px" />
                  <Column header="Attributes" style="min-width: 220px">
                    <template #body="{ data }">
                      <div class="flex flex-wrap gap-1">
                        <Tag v-if="data.color" :value="data.color" severity="info" />
                        <Tag v-if="data.size" :value="data.size" severity="secondary" />
                        <Tag v-if="data.material" :value="data.material" severity="success" />
                        <Tag v-if="data.finish" :value="data.finish" severity="warn" />
                      </div>
                    </template>
                  </Column>
                  <Column header="Price Adj." style="width: 140px">
                    <template #body="{ data }">
                      {{ formatCurrencyPHP(data.price_adjustment || 0) }}
                    </template>
                  </Column>
                  <Column header="Status" style="width: 120px">
                    <template #body="{ data }">
                      <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
                    </template>
                  </Column>
                  <Column header="Actions" style="width: 140px">
                    <template #body="{ data }">
                      <div class="flex items-center gap-1">
                        <Button icon="pi pi-pencil" text rounded severity="warning" @click="openEditVariationDialog(data)" />
                        <Button icon="pi pi-trash" text rounded severity="danger" @click="removeVariation(data)" />
                      </div>
                    </template>
                  </Column>
                </DataTable>
              </template>
            </section>
          </div>
        </div>

        <!-- Ecommerce Preview (right) -->
        <aside class="lg:sticky lg:top-6">
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Ecommerce Preview</p>
                  <p class="text-sm text-gray-600 mt-1">Updates live as you edit the form</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500">3D</span>
                    <ToggleSwitch v-model="previewShow3d" />
                  </div>
                  <Tag :value="form.is_active ? 'Active' : 'Inactive'" :severity="form.is_active ? 'success' : 'secondary'" />
                </div>
              </div>
            </div>

            <div class="p-5 space-y-5">
              <!-- Media -->
              <div class="rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden">
                <div class="aspect-square relative">
                  <Model3DPreview
                    v-if="previewShow3d && previewModelUrl"
                    :model-url="previewModelUrl"
                    :model-format="previewUsesVariation3d ? (selectedVariation3dAsset?.model_format) : existingModel?.model_format"
                    :auth-token="previewAuthToken"
                    :camera-x="previewUsesVariation3d ? Number(selectedVariation3dAsset?.default_camera_angle_x ?? 0) : form.default_camera_angle_x"
                    :camera-y="previewUsesVariation3d ? Number(selectedVariation3dAsset?.default_camera_angle_y ?? 15) : form.default_camera_angle_y"
                    :zoom="previewUsesVariation3d ? Number(selectedVariation3dAsset?.default_zoom_level ?? 1.5) : form.default_zoom_level"
                    height="100%"
                  />
                  <img
                    v-else-if="previewPrimaryImageUrl"
                    :src="previewPrimaryImageUrl"
                    alt="Product image preview"
                    class="absolute inset-0 w-full h-full object-cover"
                  />
                  <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                      <i class="pi pi-image text-3xl block mb-2"></i>
                      <p class="text-xs font-medium">No image yet</p>
                    </div>
                  </div>

                  <div class="absolute top-3 left-3 flex flex-wrap gap-2">
                    <Tag v-if="form.is_new_arrival" value="New" severity="info" />
                    <Tag v-if="form.is_bestseller" value="Bestseller" severity="success" />
                    <Tag v-if="form.is_featured" value="Featured" severity="warn" />
                  </div>
                </div>
              </div>

              <!-- Image Selector (Base product only) -->
              <div v-if="!previewUsesVariationImage && basePreviewImages.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                <button
                  v-for="(img, idx) in basePreviewImages"
                  :key="img.key"
                  type="button"
                  class="shrink-0 w-14 h-14 rounded-xl overflow-hidden border transition-colors"
                  :class="idx === selectedBaseImageIndex ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'"
                  @click="selectedBaseImageIndex = idx"
                >
                  <img :src="img.src" :alt="img.alt" class="w-full h-full object-cover" />
                </button>
              </div>

              <!-- Variations (Preview Selector) -->
              <div v-if="variations.length" class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Variation</p>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="v in variations"
                    :key="v.id"
                    type="button"
                    size="small"
                    :label="v.variation_name"
                    class="rounded-full"
                    :outlined="Number(selectedVariationId) !== Number(v.id)"
                    :severity="Number(selectedVariationId) === Number(v.id) ? 'info' : 'secondary'"
                    @click="selectedVariationId = Number(v.id); previewShow3d = true"
                  />
                </div>
                <small class="text-xs text-gray-500">Default preview shows base product images. Select a variation to preview its 3D + photo.</small>
              </div>

              <!-- Title + Meta -->
              <div class="space-y-2">
                <p class="text-lg font-semibold text-gray-900 leading-snug">
                  {{ previewName }}
                </p>
                <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                  <span v-if="form.brand">{{ form.brand }}</span>
                  <span v-if="form.brand && form.collection_name" class="text-gray-300">•</span>
                  <span v-if="form.collection_name">{{ form.collection_name }}</span>
                </div>
                <p class="text-xs text-gray-500">
                  SKU: <span class="font-mono font-semibold text-gray-700">{{ form.sku || '—' }}</span>
                </p>
              </div>

              <!-- Price -->
              <div class="space-y-1">
                <div class="flex items-end gap-2">
                  <p class="text-2xl font-semibold" :class="previewHasDiscount ? 'text-red-600' : 'text-gray-900'">
                    {{ formatCurrencyPHP(previewDisplayPrice) }}
                  </p>
                  <p v-if="previewHasDiscount" class="text-sm text-gray-500 line-through pb-0.5">
                    {{ formatCurrencyPHP(previewOriginalPrice) }}
                  </p>
                </div>
                <p class="text-xs text-gray-500">
                  {{ form.stock_status || 'In Stock' }}
                  <span v-if="form.assembly_required" class="ml-2 text-gray-400">•</span>
                  <span v-if="form.assembly_required" class="ml-2">Assembly required</span>
                </p>
              </div>

              <!-- Description -->
              <div class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Description</p>
                <div v-if="previewDescriptionHtml" class="text-sm text-gray-700 leading-relaxed [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_a]:text-blue-600 [&_a]:underline" v-html="previewDescriptionHtml"></div>
                <p v-else class="text-sm text-gray-500">
                  Add a description to help customers understand the product.
                </p>
              </div>

              <!-- Dimensions -->
              <div class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Specs</p>
                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] text-gray-500">L</p>
                    <p class="font-semibold text-gray-900">{{ previewLengthCm ?? '—' }}<span v-if="previewLengthCm"> cm</span></p>
                  </div>
                  <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] text-gray-500">W</p>
                    <p class="font-semibold text-gray-900">{{ previewWidthCm ?? '—' }}<span v-if="previewWidthCm"> cm</span></p>
                  </div>
                  <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] text-gray-500">H</p>
                    <p class="font-semibold text-gray-900">{{ previewHeightCm ?? '—' }}<span v-if="previewHeightCm"> cm</span></p>
                  </div>
                  <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] text-gray-500">Weight</p>
                    <p class="font-semibold text-gray-900">{{ previewWeightKg ?? '—' }}<span v-if="previewWeightKg"> kg</span></p>
                  </div>
                </div>
              </div>

              <!-- 3D Model Hint -->
              <div v-if="form.modelFile || existingModelPreviewUrl" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                <div class="flex items-start gap-3">
                  <div class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center">
                    <i class="pi pi-cube text-gray-700"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">3D model attached</p>
                    <p v-if="form.modelFile" class="text-xs text-gray-600 truncate">{{ form.modelFile.name }}</p>
                    <p v-else class="text-xs text-gray-600">Existing model will be shown on product page.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </form>
  
    <!-- Crop Dialog -->
    <Dialog v-model:visible="cropDialogVisible" header="Crop Image to Square" :modal="true" class="rounded-2xl"
      :style="{ width: '500px' }">
      <div class="space-y-4">
        <div class="relative mx-auto bg-gray-100 rounded-xl overflow-hidden"
          :style="{ width: cropViewportSize + 'px', height: cropViewportSize + 'px' }">
          <img v-if="cropImageUrl" :src="cropImageUrl" alt="Crop preview" class="absolute" :style="{
                      width: '100%',
                      height: '100%',
                      objectFit: 'cover',
                      transform: `scale(${cropZoom}) translate(${cropOffsetX / cropZoom}px, ${cropOffsetY / cropZoom}px)`
                    }" />
        </div>
  
        <div class="space-y-3">
          <div>
            <label class="text-xs font-medium text-gray-500 block mb-1">Zoom</label>
            <input v-model.number="cropZoom" type="range" min="1" max="3" step="0.05" class="w-full" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-500 block mb-1">Horizontal Position</label>
              <input v-model.number="cropOffsetX" type="range" min="-180" max="180" step="1" class="w-full" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 block mb-1">Vertical Position</label>
              <input v-model.number="cropOffsetY" type="range" min="-180" max="180" step="1" class="w-full" />
            </div>
          </div>
        </div>
      </div>
  
      <template #footer>
        <Button label="Skip" severity="secondary" outlined @click="skipCurrentCrop" class="rounded-xl" />
        <Button label="Crop & Continue" severity="info" @click="applyCropAndNext" class="rounded-xl" />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="variationDialogVisible"
      :header="editingVariationId ? 'Edit Variation' : 'Add Variation'"
      :modal="true"
      :style="{ width: '680px', maxWidth: '95vw' }"
    >
      <VariationFormDialog
        embedded
        :embedded-product="{
          id: Number(route.params.id),
          product_name: form.product_name,
          sku: form.sku,
          base_price: Number(form.base_price || 0)
        }"
        :embedded-variation="editingVariationId ? (variations.find(v => Number(v.id) === Number(editingVariationId)) || { id: editingVariationId }) : null"
        @saved="handleVariationSaved"
        @cancel="closeVariationDialog"
      />
    </Dialog>
  
    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import ConfirmDialog from 'primevue/confirmdialog'
import { useAuthStore } from '../../../../stores/auth'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import VariationFormDialog from '../variations/VariationForm.vue'
import merchandisingService from '../../../../services/merchandising.service'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

const isEditMode = computed(() => !!route.params.id)
const isRawMaterialType = computed(() => form.value.product_type === 'raw_material')
const submitting = ref(false)
const loadingData = ref(false)
const loadingCategories = ref(false)
const existingModel = ref(null)
const existingImages = ref<any[]>([])
const originalBasePrice = ref(0)
const cropDialogVisible = ref(false)
const cropQueue = ref<File[]>([])
const croppedFiles = ref<File[]>([])
const currentCropFile = ref<File | null>(null)
const imageUploadBaseFiles = ref<File[]>([])
type CropSettings = { zoom: number; offsetX: number; offsetY: number }
const cropSettingsByKey = ref<Record<string, CropSettings>>({})
const modelInput = ref<HTMLInputElement | null>(null)
const cropImageUrl = ref('')
const cropZoom = ref(1)
const cropOffsetX = ref(0)
const cropOffsetY = ref(0)
const cropViewportSize = 320
const loadingVariations = ref(false)
const variations = ref<any[]>([])
const selectedVariationId = ref<number | null>(null)
const variationDialogVisible = ref(false)
const variationSubmitting = ref(false)
const editingVariationId = ref<number | null>(null)
const variationErrors = ref<Record<string, string>>({})
const variationForm = ref({
  variation_sku: '',
  variation_name: '',
  color: '',
  size: '',
  material: '',
  finish: '',
  pattern: '',
  price_adjustment: 0,
  length_cm: null as number | null,
  width_cm: null as number | null,
  height_cm: null as number | null,
  weight_kg: null as number | null,
  is_active: true
})
const variation3dFile = ref<File | null>(null)
const variationImageFile = ref<File | null>(null)
const variation3dCameraX = ref(0)
const variation3dCameraY = ref(15)
const variation3dZoom = ref(1.5)

const variationFinalPricePreview = computed(() => {
  const base = Number(form.value.base_price ?? 0)
  const adjustment = Number(variationForm.value.price_adjustment ?? 0)
  return base + adjustment
})

const getModelPreviewUrl = (model: any): string => {
  return model?.url || model?.auth_url || model?.file_url || model?.model_url || ''
}

const existingModelPreviewUrl = computed(() => getModelPreviewUrl(existingModel.value))
const previewAuthToken = computed(() => authStore.token || localStorage.getItem('auth_token') || localStorage.getItem('access_token'))

const form = ref({
  product_name: '',
  sku: '',
  category_id: null,
  subcategory_id: null,
  unit_code: null as string | null,
  product_type: 'finished_good',
  brand: '',
  collection_name: '',
  stock_status: 'In Stock',
  description: '',
  base_price: null,
  cost_price: '',
  discounted_price: null,
  tax_rate: null,
  length_cm: null,
  width_cm: null,
  height_cm: null,
  weight_kg: null,
  assembly_required: false,
  is_featured: false,
  is_new_arrival: false,
  is_bestseller: false,
  is_active: true,
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  published_at: null,
  price_change_reason: '',
  price_approval_status: 'approved',
  // 3D Model fields
  modelFile: null,
  imageFiles: [],
  default_camera_angle_x: 0,
  default_camera_angle_y: 15,
  default_zoom_level: 1.5
})

const errors = ref<Record<string, string>>({})
const categories = ref([])
const units = ref<any[]>([])
const productTypeOptions = [
  { label: 'Finished Good', value: 'finished_good' }
]
const subcategories = computed(() => {
  if (!form.value.category_id) return []
  return categories.value.filter((c: any) => c.parent_category_id === form.value.category_id)
})

const previewShow3d = ref(false)
const previewModelObjectUrl = ref<string>('')

const previewName = computed(() => (form.value.product_name || '').trim() || 'Untitled product')
const previewDescriptionHtml = computed(() => String(form.value.description || '').trim())
const previewHasDiscount = computed(() => {
  if (previewUsesVariationPricing.value) return false
  const base = Number(form.value.base_price ?? 0)
  const discounted = Number(form.value.discounted_price ?? 0)
  return discounted > 0 && discounted < base
})
const previewDisplayPrice = computed(() => {
  if (previewUsesVariationPricing.value) {
    const v: any = selectedVariation.value
    const final = Number(v?.final_price ?? 0)
    if (final > 0) return final
    const base = Number(form.value.base_price ?? 0)
    const adj = Number(v?.price_adjustment ?? 0)
    return base + adj
  }
  if (previewHasDiscount.value) return Number(form.value.discounted_price ?? 0)
  return Number(form.value.base_price ?? 0)
})
const previewOriginalPrice = computed(() => {
  if (previewUsesVariationPricing.value) return Number(form.value.base_price ?? 0)
  return Number(form.value.base_price ?? 0)
})

const selectedVariation = computed(() => {
  if (!selectedVariationId.value) return null
  return (variations.value || []).find((v: any) => Number(v?.id) === Number(selectedVariationId.value)) || null
})
const selectedVariation3dAsset = computed(() => {
  const v: any = selectedVariation.value
  return v?.custom3dModel || v?.custom3d_model || v?.custom_3d_model || null
})
const selectedVariationImageAsset = computed(() => {
  const v: any = selectedVariation.value
  return v?.customImage || v?.custom_image || v?.custom_image_asset || null
})
const selectedVariationModelUrl = computed(() => {
  return getModelPreviewUrl(selectedVariation3dAsset.value)
})
const selectedVariationImageUrl = computed(() => {
  const img: any = selectedVariationImageAsset.value
  return img?.url || img?.auth_url || img?.file_url || img?.image_url || ''
})
// Pricing follows the selected variation (if any), even when it has no media/spec overrides.
const previewUsesVariationPricing = computed(() => (variations.value || []).length > 0 && !!selectedVariation.value)

// Media/specs fall back to parent when the variation has nothing to show.
const variationHasOwnMediaOrSpecs = computed(() => {
  const v: any = selectedVariation.value
  if (!v) return false
  const has3d = !!selectedVariationModelUrl.value
  const hasImage = !!String(selectedVariationImageUrl.value || '').trim()
  const hasSpecs = ['length_cm', 'width_cm', 'height_cm', 'weight_kg'].some((k) => {
    const val = v?.[k]
    return val !== null && val !== undefined && val !== ''
  })
  return has3d || hasImage || hasSpecs
})
const previewUsesVariationAssets = computed(() => previewUsesVariationPricing.value && variationHasOwnMediaOrSpecs.value)
const previewUsesVariation3d = computed(() => previewUsesVariationPricing.value && !!selectedVariationModelUrl.value)
const previewUsesVariationImage = computed(() => previewUsesVariationPricing.value && !!String(selectedVariationImageUrl.value || '').trim())
const previewUsesVariationSpecs = computed(() => {
  const v: any = selectedVariation.value
  if (!previewUsesVariationPricing.value || !v) return false
  return ['length_cm', 'width_cm', 'height_cm', 'weight_kg'].some((k) => {
    const val = v?.[k]
    return val !== null && val !== undefined && val !== ''
  })
})

const basePreviewImages = computed(() => {
  const items: Array<{ key: string; src: string; alt: string }> = []

  const existing = Array.isArray(existingImages.value) ? [...existingImages.value] : []
  existing.sort((a: any, b: any) => Number(!!b?.is_primary) - Number(!!a?.is_primary))
  for (const img of existing) {
    if (!img?.url) continue
    items.push({
      key: `existing-${img?.id ?? Math.random()}`,
      src: img.url,
      alt: img?.file_name || 'Product image'
    })
  }

  const newFiles = Array.isArray(form.value.imageFiles) ? form.value.imageFiles : []
  newFiles.forEach((file: File, index: number) => {
    items.push({
      key: `new-${index}`,
      src: getImagePreview(file),
      alt: file?.name || 'Product image'
    })
  })

  return items.filter((i) => !!i.src).slice(0, 4)
})

const selectedBaseImageIndex = ref(0)
watch(basePreviewImages, (items) => {
  if (!items.length) {
    selectedBaseImageIndex.value = 0
    return
  }
  if (selectedBaseImageIndex.value >= items.length) {
    selectedBaseImageIndex.value = 0
  }
})

const previewImageUrl = computed(() => {
  if (previewUsesVariationImage.value) return selectedVariationImageUrl.value || ''
  const items = basePreviewImages.value
  return items[selectedBaseImageIndex.value]?.src || items[0]?.src || ''
})
const previewLengthCm = computed(() => previewUsesVariationSpecs.value ? (selectedVariation.value?.length_cm ?? null) : form.value.length_cm)
const previewWidthCm = computed(() => previewUsesVariationSpecs.value ? (selectedVariation.value?.width_cm ?? null) : form.value.width_cm)
const previewHeightCm = computed(() => previewUsesVariationSpecs.value ? (selectedVariation.value?.height_cm ?? null) : form.value.height_cm)
const previewWeightKg = computed(() => previewUsesVariationSpecs.value ? (selectedVariation.value?.weight_kg ?? null) : form.value.weight_kg)

const previewPrimaryImageUrl = computed(() => previewImageUrl.value)

const previewModelUrl = computed(() => {
  if (previewModelObjectUrl.value) return previewModelObjectUrl.value
  if (previewUsesVariation3d.value && selectedVariationModelUrl.value) return selectedVariationModelUrl.value
  return existingModelPreviewUrl.value || ''
})

const previewHas3d = computed(() => {
  if (previewUsesVariation3d.value) return !!selectedVariationModelUrl.value
  return !!(form.value.modelFile || existingModelPreviewUrl.value)
})

const maxProductImages = 4
const remainingImageSlots = computed(() => {
  const existingCount = Array.isArray(existingImages.value) ? existingImages.value.length : 0
  const newCount = Array.isArray(form.value.imageFiles) ? form.value.imageFiles.length : 0
  return Math.max(0, maxProductImages - existingCount - newCount)
})

const previewGalleryItems = computed(() => {
  const items: Array<{
    key: string
    src: string
    alt: string
    type: 'existing' | 'new'
    isPrimary: boolean
    raw?: any
    index?: number
  }> = []

  if (Array.isArray(existingImages.value)) {
    for (const img of existingImages.value) {
      items.push({
        key: `existing-${img?.id ?? Math.random()}`,
        src: img?.url || '',
        alt: img?.file_name || 'Product image',
        type: 'existing',
        isPrimary: !!img?.is_primary,
        raw: img
      })
    }
  }

  if (Array.isArray(form.value.imageFiles)) {
    form.value.imageFiles.forEach((file: File, index: number) => {
      items.push({
        key: `new-${index}`,
        src: getImagePreview(file),
        alt: file?.name || 'Product image',
        type: 'new',
        isPrimary: existingImages.value?.length ? false : index === 0,
        index
      })
    })
  }

  return items.filter((i) => !!i.src)
})

const selectedAssetPreviewIndex = ref(0)
watch(previewGalleryItems, (items) => {
  if (!items.length) {
    selectedAssetPreviewIndex.value = 0
    return
  }
  if (selectedAssetPreviewIndex.value >= items.length) {
    selectedAssetPreviewIndex.value = 0
  }
})
const selectedAssetPreviewItem = computed(() => previewGalleryItems.value[selectedAssetPreviewIndex.value] || null)

watch(
  () => form.value.modelFile,
  (file) => {
    if (previewModelObjectUrl.value) {
      URL.revokeObjectURL(previewModelObjectUrl.value)
      previewModelObjectUrl.value = ''
    }
    if (file instanceof File) {
      previewModelObjectUrl.value = URL.createObjectURL(file)
    }

    if (!previewHas3d.value) {
      previewShow3d.value = false
    }
  }
)

watch(previewHas3d, (has3d) => {
  if (!has3d) previewShow3d.value = false
})

onBeforeUnmount(() => {
  if (previewModelObjectUrl.value) URL.revokeObjectURL(previewModelObjectUrl.value)
})

// Hard-coded unit values for the select (UI-driven)
const unitOptions = [
  { label: 'Pieces (pcs)', value: 'pcs' },
  { label: 'Set (set)', value: 'set' },
  { label: 'Pair (pair)', value: 'pair' },
  { label: 'Box (box)', value: 'box' },
  { label: 'Kilogram (kg)', value: 'kg' },
  { label: 'Gram (g)', value: 'g' },
  { label: 'Pound (lb)', value: 'lb' },
  { label: 'Meter (m)', value: 'm' },
  { label: 'Centimeter (cm)', value: 'cm' },
  { label: 'Millimeter (mm)', value: 'mm' },
  { label: 'Foot (ft)', value: 'ft' },
  { label: 'Inch (in)', value: 'in' }
]

const resolveUnitId = (code: string | null) => {
  if (!code) return null
  const normalized = String(code).trim().toLowerCase()
  const match = units.value.find((unit: any) => {
    const candidates = [
      unit.unit_symbol,
      unit.unit_code,
      unit.unit_name
    ].filter(Boolean).map((v: any) => String(v).trim().toLowerCase())
    return candidates.includes(normalized)
  })
  return match?.id ?? null
}

const loadCategories = async () => {
  loadingCategories.value = true
  try {
    const response = await merchandisingService.getCategories()
    categories.value = response.data?.data || response.data?.data?.data || []
  } catch (error) {
    console.error('Failed to load categories:', error)
    categories.value = []
  } finally {
    loadingCategories.value = false
  }
}

const loadUnits = async () => {
  try {
    const response = await inventoryService.getUnits({ per_page: 200, is_active: true })
    const data = response?.data?.data || response?.data?.data?.data || response?.data || []
    units.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Failed to load units:', error)
    units.value = []
  }
}

const loadProduct = async () => {
  if (!isEditMode.value) return

  loadingData.value = true
  try {
    const response = await merchandisingService.getProduct(Number(route.params.id))
    const product = response.data || {}

    // Properly map all fields with date conversion
    Object.assign(form.value, {
      product_name: product.product_name || '',
      sku: product.sku || '',
      category_id: product.category_id,
      subcategory_id: product.subcategory_id,
      unit_code: product.unit?.unit_symbol || product.unit?.unit_code || product.unit?.unit_name || null,
      product_type: product.product_type || 'finished_good',
      brand: product.brand || '',
      collection_name: product.collection_name || '',
      stock_status: product.stock_status || 'In Stock',
      description: product.description || '',
      base_price: product.base_price,
      cost_price: product.cost_price || null,
      discounted_price: product.discounted_price,
      tax_rate: product.tax_rate || null,
      length_cm: product.length_cm,
      width_cm: product.width_cm,
      height_cm: product.height_cm,
      weight_kg: product.weight_kg,
      assembly_required: product.assembly_required || false,
      is_featured: product.is_featured || false,
      is_new_arrival: product.is_new_arrival || false,
      is_bestseller: product.is_bestseller || false,
      is_active: product.is_active || true,
      meta_title: product.meta_title || '',
      meta_description: product.meta_description || '',
      meta_keywords: product.meta_keywords || '',
      // Convert string date to Date object for DatePicker
      published_at: product.published_at ? new Date(product.published_at) : null,
      price_change_reason: '',
      price_approval_status: product.price_approval_status || 'approved',
      // Keep existing 3D settings
      default_camera_angle_x: form.value.default_camera_angle_x,
      default_camera_angle_y: form.value.default_camera_angle_y,
      default_zoom_level: form.value.default_zoom_level
    })

    if (product.product_type === 'raw_material') {
      toast.add({
        severity: 'warn',
        summary: 'Not Available',
        detail: 'Raw materials are hidden in merchandising.',
        life: 3000
      })
      router.push({ name: 'merchandising.products' })
      return
    }

    originalBasePrice.value = product.base_price

    // Load 3D models
    if (product.id) {
      await loadProductAssets(product.id)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product',
      life: 5000
    })
    router.push({ name: 'merchandising.products' })
  } finally {
    loadingData.value = false
  }
}

const loadProductAssets = async (productId: number) => {
  try {
    const response = await merchandisingService.getAssetsByProduct(productId)
    const payload = response.data || {}
    const grouped = payload.assets_by_type || payload.data?.assets_by_type || {}
    const models = grouped['3D_Model'] || []
    const mainImages = grouped['Image_Main'] || []
    const galleryImages = grouped['Image_Gallery'] || []

    existingImages.value = [...mainImages, ...galleryImages]
      .sort((a: any, b: any) => Number(a.display_order || 0) - Number(b.display_order || 0))

    if (models.length > 0) {
      existingModel.value = models.find((m: any) => m.is_primary) || models[0]
      if (existingModel.value) {
        form.value.default_camera_angle_x = existingModel.value.default_camera_angle_x || 0
        form.value.default_camera_angle_y = existingModel.value.default_camera_angle_y || 15
        form.value.default_zoom_level = existingModel.value.default_zoom_level || 1.5
      }
    }
  } catch (error) {
    console.error('Failed to load product assets:', error)
    existingImages.value = []
  }
}

const deleteExistingImage = async (asset: any) => {
  if (!asset?.id) return
  try {
    await merchandisingService.deleteAsset(asset.id)
    existingImages.value = existingImages.value.filter((img: any) => img.id !== asset.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Image deleted',
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to delete image',
      life: 3000
    })
  }
}

const onCategoryChange = () => {
  form.value.subcategory_id = null
  generateSKU()
}

// SKU Generation Logic
const generateSKU = async () => {
  if (!form.value.product_name || !form.value.category_id) return

  const category = categories.value.find((c: any) => c.id === form.value.category_id)
  if (!category) return

  // Get category code
  const categoryCode = category.category_code || 'GEN'

  // Get brand initial or use first letter of product name
  const brandCode = form.value.brand
    ? form.value.brand.substring(0, 3).toUpperCase()
    : form.value.product_name.substring(0, 3).toUpperCase()

  // Generate base SKU
  const baseSKU = `${categoryCode}-${brandCode}`

  // Check for uniqueness and get next sequence
  try {
    const response = await merchandisingService.getProducts({ search: baseSKU, product_type: 'finished_good' })
    const existingProducts = response.data?.data || response.data?.data?.data || []

    // Find highest sequence number
    let maxSequence = 0
    existingProducts.forEach((p: any) => {
      const match = p.sku?.match(new RegExp(`${baseSKU}-(\\d+)`))
      if (match) {
        const seq = parseInt(match[1])
        if (seq > maxSequence) maxSequence = seq
      }
    })

    // Generate new sequence
    const nextSequence = (maxSequence + 1).toString().padStart(3, '0')
    form.value.sku = `${baseSKU}-${nextSequence}`
  } catch (error) {
    // Fallback to random sequence if API fails
    const randomSeq = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
    form.value.sku = `${baseSKU}-${randomSeq}`
  }
}

const copySKU = () => {
  if (!form.value.sku) return
  navigator.clipboard.writeText(form.value.sku)
  toast.add({
    severity: 'success',
    summary: 'Copied!',
    detail: 'SKU copied to clipboard',
    life: 2000
  })
}

const handleModelSelect = (event: any) => {
  const file = event.target.files[0]
  if (!file) return

  if (file.size > 100000000) {
    toast.add({
      severity: 'error',
      summary: 'File too large',
      detail: 'Model file must be less than 100MB',
      life: 3000
    })
    return
  }

  form.value.modelFile = file
}

const handleModelDrop = (event: DragEvent) => {
  const file = event.dataTransfer?.files[0]
  if (!file) return

  if (!file.name.endsWith('.glb') && !file.name.endsWith('.gltf')) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file',
      detail: 'Only GLB and GLTF files are supported',
      life: 3000
    })
    return
  }

  form.value.modelFile = file
}

const removeModel = () => {
  if (!form.value.modelFile) return
  confirm.require({
    header: 'Remove 3D model?',
    message: 'This will remove the selected file from the form (it will not delete anything already uploaded).',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Remove',
    rejectLabel: 'Cancel',
    accept: () => {
      form.value.modelFile = null
      if (modelInput.value) modelInput.value.value = ''
    },
  })
}

const deleteExistingModel = async () => {
  if (!existingModel.value?.id) return

  confirm.require({
    header: 'Delete existing 3D model?',
    message: 'This will permanently delete the uploaded 3D model from the product.',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Delete',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        await merchandisingService.deleteAsset(existingModel.value.id)
        existingModel.value = null
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: '3D model deleted',
          life: 3000,
        })
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete model',
          life: 3000,
        })
      }
    },
  })
}

const resetCropAdjustments = () => {
  cropZoom.value = 1
  cropOffsetX.value = 0
  cropOffsetY.value = 0
}

const startCropQueue = () => {
  if (!cropQueue.value.length) {
    cropDialogVisible.value = false
    form.value.imageFiles = [...imageUploadBaseFiles.value, ...croppedFiles.value]
    return
  }

  currentCropFile.value = cropQueue.value[0]
  cropImageUrl.value = URL.createObjectURL(currentCropFile.value)
  resetCropAdjustments()
  cropDialogVisible.value = true
}

const finalizeCurrentCrop = (fileToAppend: File) => {
  croppedFiles.value.push(fileToAppend)
  cropQueue.value.shift()

  if (cropImageUrl.value) {
    URL.revokeObjectURL(cropImageUrl.value)
    cropImageUrl.value = ''
  }

  if (cropQueue.value.length) {
    startCropQueue()
  } else {
    cropDialogVisible.value = false
    form.value.imageFiles = [...imageUploadBaseFiles.value, ...croppedFiles.value]
  }
}

const fileKey = (file: File) => {
  return [file.name, file.size, file.lastModified].join('|')
}

const createCroppedFileFrom = async (sourceFile: File, settings: CropSettings) => {
  const objectUrl = URL.createObjectURL(sourceFile)

  return await new Promise<File | null>((resolve) => {
    const image = new Image()
    image.onload = () => {
      try {
        const canvas = document.createElement('canvas')
        const outputSize = 1200
        canvas.width = outputSize
        canvas.height = outputSize

        const ctx = canvas.getContext('2d')
        if (!ctx) {
          resolve(null)
          return
        }

        const naturalW = image.naturalWidth
        const naturalH = image.naturalHeight
        const baseScale = Math.max(cropViewportSize / naturalW, cropViewportSize / naturalH)
        const finalScale = baseScale * settings.zoom

        const renderedW = naturalW * finalScale
        const renderedH = naturalH * finalScale
        const renderedLeft = (cropViewportSize - renderedW) / 2 + settings.offsetX
        const renderedTop = (cropViewportSize - renderedH) / 2 + settings.offsetY

        let sx = (-renderedLeft) / finalScale
        let sy = (-renderedTop) / finalScale
        let sw = cropViewportSize / finalScale
        let sh = cropViewportSize / finalScale

        sx = Math.max(0, Math.min(sx, naturalW - 1))
        sy = Math.max(0, Math.min(sy, naturalH - 1))
        sw = Math.min(sw, naturalW - sx)
        sh = Math.min(sh, naturalH - sy)

        ctx.drawImage(image, sx, sy, sw, sh, 0, 0, outputSize, outputSize)
        canvas.toBlob((blob) => {
          if (!blob) {
            resolve(null)
            return
          }
          const fileName = sourceFile?.name || `image-${Date.now()}.jpg`
          resolve(new File([blob], fileName, { type: 'image/jpeg' }))
        }, 'image/jpeg', 0.92)
      } finally {
        URL.revokeObjectURL(objectUrl)
      }
    }
    image.onerror = () => resolve(null)
    image.src = objectUrl
  })
}

const applyCropAndNext = async () => {
  if (!currentCropFile.value) return
  cropSettingsByKey.value[fileKey(currentCropFile.value)] = {
    zoom: cropZoom.value,
    offsetX: cropOffsetX.value,
    offsetY: cropOffsetY.value
  }
  // Keep the original file in state; cropping is applied only on submit/upload.
  finalizeCurrentCrop(currentCropFile.value)
}

const skipCurrentCrop = () => {
  if (!currentCropFile.value) return
  delete cropSettingsByKey.value[fileKey(currentCropFile.value)]
  finalizeCurrentCrop(currentCropFile.value)
}

const handleImageSelect = (event: any) => {
  const incomingFiles = Array.from(event.files || []) as File[]
  if (!incomingFiles.length) return

  const availableSlots = remainingImageSlots.value
  if (availableSlots <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Image limit reached',
      detail: `You can only upload up to ${maxProductImages} images.`,
      life: 3200
    })
    return
  }

  const limitedFiles = incomingFiles.slice(0, availableSlots)
  if (limitedFiles.length < incomingFiles.length) {
    toast.add({
      severity: 'info',
      summary: 'Some files skipped',
      detail: `Only the first ${limitedFiles.length} image(s) were added (max ${maxProductImages}).`,
      life: 3200
    })
  }

  imageUploadBaseFiles.value = Array.isArray(form.value.imageFiles) ? [...form.value.imageFiles] : []
  cropQueue.value = limitedFiles
  croppedFiles.value = []
  startCropQueue()
}

const getImagePreview = (file: File) => {
  return URL.createObjectURL(file)
}

const removeImage = (index: number) => {
  form.value.imageFiles.splice(index, 1)
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatCurrencyPHP = (value: number | string | null | undefined) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(value || 0))
}

const generateVariationSku = () => {
  const baseSKU = String(form.value.sku || '').trim().toUpperCase()
  if (!baseSKU) {
    variationForm.value.variation_sku = ''
    return
  }

  const attributes = [
    variationForm.value.color?.substring(0, 3).toUpperCase(),
    variationForm.value.size?.substring(0, 2).toUpperCase(),
    variationForm.value.material?.substring(0, 3).toUpperCase(),
    variationForm.value.finish?.substring(0, 2).toUpperCase()
  ].filter(Boolean).join('-')

  variationForm.value.variation_sku = attributes ? `${baseSKU}-${attributes}` : baseSKU
}

const resetVariationForm = () => {
  editingVariationId.value = null
  variationErrors.value = {}
  variationForm.value = {
    variation_sku: '',
    variation_name: '',
    color: '',
    size: '',
    material: '',
    finish: '',
    pattern: '',
    price_adjustment: 0,
    length_cm: null,
    width_cm: null,
    height_cm: null,
    weight_kg: null,
    is_active: true
  }
  variation3dFile.value = null
  variationImageFile.value = null
  variation3dCameraX.value = 0
  variation3dCameraY.value = 15
  variation3dZoom.value = 1.5
  generateVariationSku()
}

const loadVariations = async () => {
  if (!isEditMode.value) return
  loadingVariations.value = true

  try {
    const response = await merchandisingService.getVariationsByProduct(Number(route.params.id))
    const payload = response?.data || {}
    const list = payload?.variations || payload?.data?.variations || payload?.data || []
    variations.value = Array.isArray(list) ? list : []

    // Default preview is base product (no selected variation).
    if (selectedVariationId.value && !variations.value.find((v: any) => Number(v?.id) === Number(selectedVariationId.value))) {
      selectedVariationId.value = null
    }
  } catch (error) {
    console.error('Failed to load variations:', error)
    variations.value = []
  } finally {
    loadingVariations.value = false
  }
}

const openCreateVariationDialog = () => {
  resetVariationForm()
  variationDialogVisible.value = true
}

const openEditVariationDialog = (row: any) => {
  editingVariationId.value = Number(row.id)
  variationErrors.value = {}
  variationForm.value = {
    variation_sku: row.variation_sku || '',
    variation_name: row.variation_name || '',
    color: row.color || '',
    size: row.size || '',
    material: row.material || '',
    finish: row.finish || '',
    pattern: row.pattern || '',
    price_adjustment: Number(row.price_adjustment || 0),
    length_cm: row.length_cm ?? null,
    width_cm: row.width_cm ?? null,
    height_cm: row.height_cm ?? null,
    weight_kg: row.weight_kg ?? null,
    is_active: !!row.is_active
  }
  variation3dFile.value = null
  variationImageFile.value = null
  variation3dCameraX.value = Number(form.value.default_camera_angle_x ?? 0)
  variation3dCameraY.value = Number(form.value.default_camera_angle_y ?? 15)
  variation3dZoom.value = Number(form.value.default_zoom_level ?? 1.5)
  variationDialogVisible.value = true
}

const closeVariationDialog = () => {
  variationDialogVisible.value = false
}

const handleVariationSaved = async () => {
  closeVariationDialog()
  await loadVariations()
}

const validateVariationForm = () => {
  variationErrors.value = {}
  if (!variationForm.value.variation_name?.trim()) {
    variationErrors.value.variation_name = 'Variation name is required'
  }
  if (!variationForm.value.variation_sku?.trim()) {
    variationErrors.value.variation_sku = 'Variation SKU is required'
  }
  if (!editingVariationId.value && !variationImageFile.value) {
    variationErrors.value.custom_image = 'Variation photo is required'
  }
  return Object.keys(variationErrors.value).length === 0
}

const handleVariation3dSelect = (event: any) => {
  const file = (event?.files && event.files[0]) ? event.files[0] : null
  variation3dFile.value = file
}

const handleVariationImageSelect = (event: any) => {
  const file = (event?.files && event.files[0]) ? event.files[0] : null
  variationImageFile.value = file
}

const getVariationImagePreview = () => {
  if (!variationImageFile.value) return ''
  return URL.createObjectURL(variationImageFile.value)
}

const saveVariation = async () => {
  if (!isEditMode.value) return
  generateVariationSku()
  if (!validateVariationForm()) return

  variationSubmitting.value = true
  try {
    const productId = Number(route.params.id)
    const submitData: any = {
      product_id: productId,
      variation_sku: variationForm.value.variation_sku,
      variation_name: variationForm.value.variation_name,
      color: variationForm.value.color || null,
      size: variationForm.value.size || null,
      material: variationForm.value.material || null,
      finish: variationForm.value.finish || null,
      pattern: variationForm.value.pattern || null,
      price_adjustment: Number(variationForm.value.price_adjustment || 0),
      final_price: Number(form.value.base_price || 0) + Number(variationForm.value.price_adjustment || 0),
      length_cm: variationForm.value.length_cm,
      width_cm: variationForm.value.width_cm,
      height_cm: variationForm.value.height_cm,
      weight_kg: variationForm.value.weight_kg,
      is_active: variationForm.value.is_active
    }

    if (variation3dFile.value) {
      const fd = new FormData()
      fd.append('product_id', productId.toString())
      fd.append('asset_type', '3D_Model')
      fd.append('asset_file', variation3dFile.value)
      fd.append('is_primary', '0')
      fd.append('model_format', variation3dFile.value.name.split('.').pop()?.toLowerCase() || 'glb')
      fd.append('default_camera_angle_x', variation3dCameraX.value.toString())
      fd.append('default_camera_angle_y', variation3dCameraY.value.toString())
      fd.append('default_zoom_level', variation3dZoom.value.toString())
      const uploadResp: any = await merchandisingService.uploadAsset(fd)
      const assetId = uploadResp?.data?.id ?? uploadResp?.data?.data?.id ?? uploadResp?.id
      if (assetId) submitData.custom_3d_model_id = Number(assetId)
    }

    if (variationImageFile.value) {
      const fd = new FormData()
      fd.append('product_id', productId.toString())
      fd.append('asset_type', 'Image_Gallery')
      fd.append('asset_file', variationImageFile.value)
      fd.append('is_primary', '0')
      fd.append('display_order', '0')
      const uploadResp: any = await merchandisingService.uploadAsset(fd)
      const assetId = uploadResp?.data?.id ?? uploadResp?.data?.data?.id ?? uploadResp?.id
      if (assetId) submitData.custom_image_id = Number(assetId)
    }

    if (editingVariationId.value) {
      await merchandisingService.updateVariation(editingVariationId.value, submitData)
    } else {
      await merchandisingService.createVariation(submitData as any)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: editingVariationId.value ? 'Variation updated successfully' : 'Variation created successfully',
      life: 2500
    })
    variationDialogVisible.value = false
    await loadVariations()
  } catch (error: any) {
    if (error?.response?.status === 422) {
      variationErrors.value = error.response.data.errors || {}
    }
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to save variation',
      life: 3500
    })
  } finally {
    variationSubmitting.value = false
  }
}

const removeVariation = async (row: any) => {
  if (!row?.id) return
  if (!window.confirm('Delete this variation?')) return

  try {
    await merchandisingService.deleteVariation(Number(row.id))
    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: 'Variation removed',
      life: 2200
    })
    await loadVariations()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to delete variation',
      life: 3200
    })
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.product_name) errors.value.product_name = 'Product name is required'
  if (!form.value.sku) errors.value.sku = 'SKU is required'
  if (!form.value.category_id) errors.value.category_id = 'Category is required'
  if (form.value.base_price != null && form.value.base_price < 0) {
    errors.value.base_price = 'Base price must be 0 or greater'
  }

  if (form.value.cost_price != null && form.value.cost_price < 0) {
    errors.value.cost_price = 'Cost price must be 0 or greater'
  }

  if (isRawMaterialType.value) {
    form.value.discounted_price = null
    form.value.is_featured = false
    form.value.is_new_arrival = false
    form.value.is_bestseller = false
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return
  }

  submitting.value = true

  try {
    let productId: number

    // Prepare data for submission - convert Date back to ISO string
    const submitData = {
      product_name: form.value.product_name,
      sku: form.value.sku,
      category_id: form.value.category_id,
      subcategory_id: form.value.subcategory_id,
      unit_id: resolveUnitId(form.value.unit_code),
      product_type: 'finished_good',
      brand: form.value.brand,
      collection_name: form.value.collection_name,
      stock_status: form.value.stock_status,
      description: form.value.description,
      base_price: form.value.base_price,
      cost_price: form.value.cost_price,
      discounted_price: isRawMaterialType.value ? null : form.value.discounted_price,
      tax_rate: form.value.tax_rate,
      length_cm: form.value.length_cm,
      width_cm: form.value.width_cm,
      height_cm: form.value.height_cm,
      weight_kg: form.value.weight_kg,
      assembly_required: form.value.assembly_required,
      is_featured: form.value.is_featured,
      is_new_arrival: form.value.is_new_arrival,
      is_bestseller: form.value.is_bestseller,
      is_active: form.value.is_active,
      meta_title: form.value.meta_title,
      meta_description: form.value.meta_description,
      meta_keywords: form.value.meta_keywords,
      // Convert Date object to ISO string
      published_at: form.value.published_at instanceof Date
        ? form.value.published_at.toISOString()
        : form.value.published_at,
      price_change_reason: form.value.price_change_reason
    }

    // Create or update product
    if (isEditMode.value) {
      const response = await merchandisingService.updateProduct(Number(route.params.id), submitData)
      productId = Number(route.params.id)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response?.message || 'Product updated successfully',
        life: 3000
      })
    } else {
      const response = await merchandisingService.createProduct(submitData)
      productId = response.data?.id || response.data?.data?.id
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product created successfully',
        life: 3000
      })
    }

    // Upload 3D model if present
    if (form.value.modelFile) {
      await upload3DModel(productId)
    } else if (existingModel.value?.id) {
      await updateExistingModelCameraSettings(existingModel.value.id)
    }

    // Upload images if present
    if (form.value.imageFiles && form.value.imageFiles.length > 0) {
      await uploadImages(productId)
    }

    router.push({ name: 'merchandising.products' })
  } catch (error: any) {
    console.error('Form submission error:', error)

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save product',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

// In ProductForm2.vue - Update these functions

const upload3DModel = async (productId: number) => {
  if (!form.value.modelFile) return

  try {
    const formData = new FormData()
    formData.append('product_id', productId.toString())
    formData.append('asset_type', '3D_Model')
    formData.append('asset_file', form.value.modelFile) // Note: asset_file not model_file
    formData.append('is_primary', '1') // Use '1' instead of 'true'
    formData.append('model_format', form.value.modelFile.name.split('.').pop()?.toLowerCase() || 'glb')
    formData.append('default_camera_angle_x', form.value.default_camera_angle_x.toString())
    formData.append('default_camera_angle_y', form.value.default_camera_angle_y.toString())
    formData.append('default_zoom_level', form.value.default_zoom_level.toString())

    console.log('Uploading 3D model:', {
      productId,
      fileName: form.value.modelFile.name,
      size: form.value.modelFile.size
    })

    const response = await merchandisingService.uploadAsset(formData)

    console.log('3D model upload response:', response)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: '3D model uploaded successfully',
      life: 3000
    })
  } catch (error: any) {
    console.error('3D model upload error:', error.response || error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to upload 3D model',
      life: 3000
    })
    throw error // Re-throw to handle in main submit
  }
}

const updateExistingModelCameraSettings = async (assetId: number) => {
  try {
    await merchandisingService.updateAsset(assetId, {
      default_camera_angle_x: Number(form.value.default_camera_angle_x ?? 0),
      default_camera_angle_y: Number(form.value.default_camera_angle_y ?? 15),
      default_zoom_level: Number(form.value.default_zoom_level ?? 1.5),
    })
  } catch (error: any) {
    console.error('Failed to update existing model camera settings:', error?.response || error)
    toast.add({
      severity: 'warn',
      summary: 'Camera Settings',
      detail: 'Product saved, but failed to update existing 3D camera settings.',
      life: 3500
    })
  }
}

const uploadImages = async (productId: number) => {
  if (!form.value.imageFiles || form.value.imageFiles.length === 0) return

  try {
    for (let i = 0; i < form.value.imageFiles.length; i++) {
      const originalFile = form.value.imageFiles[i] as File
      const settings = cropSettingsByKey.value[fileKey(originalFile)]
      const fileToUpload = settings ? (await createCroppedFileFrom(originalFile, settings)) : originalFile
      if (!fileToUpload) {
        throw new Error('Failed to crop image')
      }

      const formData = new FormData()
      formData.append('product_id', productId.toString())
      formData.append('asset_type', i === 0 ? 'Image_Main' : 'Image_Gallery')
      formData.append('asset_file', fileToUpload) // Note: asset_file
      formData.append('is_primary', i === 0 ? '1' : '0') // Use '1'/'0'
      formData.append('display_order', i.toString())

      console.log(`Uploading image ${i + 1}/${form.value.imageFiles.length}`)

      await merchandisingService.uploadAsset(formData)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${form.value.imageFiles.length} images uploaded successfully`,
      life: 3000
    })
  } catch (error: any) {
    console.error('Image upload error:', error.response || error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to upload some images',
      life: 3000
    })
    throw error
  }
}

onMounted(() => {
  if (route.name === 'merchandising.products.raw.create') {
    toast.add({
      severity: 'info',
      summary: 'Updated Flow',
      detail: 'Raw materials are hidden in merchandising.',
      life: 2600
    })
    router.replace({ name: 'merchandising.products.create' })
    return
  }
  form.value.product_type = 'finished_good'
  loadCategories()
  loadUnits()
  loadProduct()
  loadVariations()
})
</script>
<style scoped>
/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

/* iOS-style inputs */
:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-textarea),
:deep(.p-inputnumber) {
  border-radius: 12px;
  border-color: #e5e7eb;
  background-color: #f9fafb;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-textarea:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* iOS-style buttons */
:deep(.p-button) {
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button.p-button-text) {
  border-radius: 9999px;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
