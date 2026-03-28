<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
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
              {{ isEditMode ? 'Edit Product' : (isRawMaterialForm ? 'Create Raw Material' : 'Create Product') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ isEditMode ? 'Update product information' : (isRawMaterialForm ? 'Add a new raw material to your catalog' : 'Add a new product to your catalog') }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button type="submit" :label="isEditMode ? 'Update Product' : 'Create Product'" icon="pi pi-check"
            :loading="submitting" class="rounded-xl px-5 py-2.5 bg-blue-500 hover:bg-blue-600 border-none text-white" />
        </div>
      </div>
  
      <!-- Main Content -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr]">
          <!-- iOS-style Sidebar Menu -->
          <aside class="border-b border-gray-100 lg:border-b-0 lg:border-r bg-gray-50/50">
            <div class="p-5">
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Menu</p>
              <div class="space-y-1">
                <button type="button" v-for="item in menuItems" :key="item.key" @click="activeSection = item.key" border border-gray-200
                  class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200"
                  :class="activeSection === item.key 
      ? 'bg-white text-blue-600 shadow-sm border border-gray-100' 
      : 'text-gray-600 hover:bg-white hover:text-gray-900'">
                  <i :class="item.icon" class="text-base"></i>
                  <span>{{ item.label }}</span>
                </button>
              </div>
            </div>
          </aside>
  
          <!-- Main Form Area -->
          <div class="p-6">
            <!-- Section Header -->
            <div class="mb-6 pb-4 border-b border-gray-100">
              <h2 class="text-xl font-semibold text-gray-900">
                {{ currentSection.title }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">{{ currentSection.description }}</p>
            </div>
  
            <!-- Product Information Section -->
            <div v-if="activeSection === 'product'" class="space-y-6">
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
  
              <!-- Category & Subcategory -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              </div>
  
              <!-- Product Type -->
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Product Type <span class="text-red-500">*</span>
                </label>
                <Select
                  v-if="!isRawMaterialForm"
                  v-model="form.product_type"
                  :options="productTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select product type"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl"
                />
                <InputText
                  v-else
                  model-value="Raw Material"
                  class="w-full bg-gray-100 border-gray-200 rounded-xl"
                  readonly
                />
                <small v-if="isRawMaterialForm" class="text-gray-500 text-xs">This form is locked to raw material.</small>
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
                <Textarea v-model="form.description" rows="4" placeholder="Enter detailed product description..."
                  class="w-full bg-gray-50 border-gray-200 rounded-xl resize-none" />
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
            </div>
  
            <!-- Pricing Section -->
            <div v-if="activeSection === 'pricing'" class="space-y-6">
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
            </div>
  
            <!-- Assets Section -->
            <div v-if="activeSection === 'assets'" class="space-y-6">
              <!-- 3D Model Upload -->
              <div class="space-y-3">
                <label class="text-sm font-medium text-gray-700">3D Model</label>
  
                <!-- Upload Area -->
                <div v-if="!form.modelFile && !existingModel" @click="$refs.modelInput.click()" @dragover.prevent
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
                <FileUpload mode="basic" name="images[]" accept="image/*" :maxFileSize="5000000" :multiple="true"
                  :auto="false" chooseLabel="Upload Images" class="w-full" @select="handleImageSelect" />
                <small class="text-gray-500 text-xs">JPG, PNG, WebP (Max 5MB each). Images are cropped to square.</small>
  
                <!-- New Images Preview -->
                <div v-if="form.imageFiles.length > 0" class="grid grid-cols-4 gap-3 mt-3">
                  <div v-for="(image, index) in form.imageFiles" :key="index" class="relative group">
                    <img :src="getImagePreview(image)"
                      class="w-full aspect-square object-cover rounded-xl border border-gray-200" />
                    <div class="absolute top-1 left-1">
                      <Tag v-if="index === 0" value="Primary" severity="success" class="text-xs" />
                    </div>
                    <Button icon="pi pi-times" severity="danger" rounded size="small"
                      class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click="removeImage(index)" />
                  </div>
                </div>
  
                <!-- Existing Images Preview -->
                <div v-if="existingImages.length > 0" class="mt-3">
                  <p class="text-xs font-medium text-gray-500 mb-2">Existing Images</p>
                  <div class="grid grid-cols-4 gap-3">
                    <div v-for="image in existingImages" :key="image.id" class="relative group">
                      <img :src="image.url" class="w-full aspect-square object-cover rounded-xl border border-gray-200" />
                      <div class="absolute top-1 left-1">
                        <Tag v-if="image.is_primary" value="Primary" severity="success" class="text-xs" />
                      </div>
                      <Button icon="pi pi-trash" severity="danger" rounded size="small"
                        class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        @click="deleteExistingImage(image)" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- SEO Section -->
            <div v-if="activeSection === 'seo'" class="space-y-6">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Meta Title</label>
                <InputText v-model="form.meta_title" placeholder="SEO optimized title" maxlength="60"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                <small class="text-gray-500">{{ form.meta_title?.length || 0 }}/60 characters</small>
              </div>
  
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Meta Description</label>
                <Textarea v-model="form.meta_description" rows="3" placeholder="SEO optimized description" maxlength="160"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl resize-none" />
                <small class="text-gray-500">{{ form.meta_description?.length || 0 }}/160 characters</small>
              </div>
  
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Meta Keywords</label>
                <InputText v-model="form.meta_keywords" placeholder="furniture, sofa, modern, living room"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl" />
                <small class="text-gray-500">Separate keywords with commas</small>
              </div>
  
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Publish Date</label>
                <DatePicker v-model="form.published_at" showTime hourFormat="24"
                  class="w-full bg-gray-50 border-gray-200 rounded-xl" dateFormat="yy-mm-dd" />
              </div>
            </div>
          </div>
        </div>
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
  
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import FileUpload from 'primevue/fileupload'
import Skeleton from 'primevue/skeleton'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import merchandisingService from '../../../../services/merchandising.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const isEditMode = computed(() => !!route.params.id)
const isRawMaterialForm = computed(() => route.name === 'merchandising.products.raw.create')
const isRawMaterialType = computed(() => form.value.product_type === 'raw_material')
const submitting = ref(false)
const loadingData = ref(false)
const activeSection = ref<'product' | 'pricing' | 'assets' | 'seo'>('product')
const loadingCategories = ref(false)
const existingModel = ref(null)
const existingImages = ref<any[]>([])
const originalBasePrice = ref(0)
const cropDialogVisible = ref(false)
const cropQueue = ref<File[]>([])
const croppedFiles = ref<File[]>([])
const currentCropFile = ref<File | null>(null)
const cropImageUrl = ref('')
const cropZoom = ref(1)
const cropOffsetX = ref(0)
const cropOffsetY = ref(0)
const cropViewportSize = 320

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
  product_type: 'finished_good',
  brand: '',
  collection_name: '',
  stock_status: 'In Stock',
  description: '',
  base_price: null,
  cost_price: '',
  discounted_price: null,
  tax_rate: 12.00,
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
const productTypeOptions = [
  { label: 'Finished Good', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' }
]
const subcategories = computed(() => {
  if (!form.value.category_id) return []
  return categories.value.filter((c: any) => c.parent_category_id === form.value.category_id)
})

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
      product_type: product.product_type || 'finished_good',
      brand: product.brand || '',
      collection_name: product.collection_name || '',
      stock_status: product.stock_status || 'In Stock',
      description: product.description || '',
      base_price: product.base_price,
      cost_price: product.cost_price || null,
      discounted_price: product.discounted_price,
      tax_rate: product.tax_rate || 12.00,
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
    const response = await merchandisingService.getProducts({ search: baseSKU })
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
  form.value.modelFile = null
  if ($refs.modelInput) {
    $refs.modelInput.value = null
  }
}

const deleteExistingModel = async () => {
  if (!existingModel.value?.id) return

  try {
    await merchandisingService.deleteAsset(existingModel.value.id)
    existingModel.value = null
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: '3D model deleted',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete model',
      life: 3000
    })
  }
}

const resetCropAdjustments = () => {
  cropZoom.value = 1
  cropOffsetX.value = 0
  cropOffsetY.value = 0
}

const startCropQueue = () => {
  if (!cropQueue.value.length) {
    cropDialogVisible.value = false
    form.value.imageFiles = [...croppedFiles.value]
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
    form.value.imageFiles = [...croppedFiles.value]
  }
}

const createCroppedFile = async () => {
  if (!currentCropFile.value || !cropImageUrl.value) return null

  return await new Promise<File | null>((resolve) => {
    const image = new Image()
    image.onload = () => {
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
      const finalScale = baseScale * cropZoom.value

      const renderedW = naturalW * finalScale
      const renderedH = naturalH * finalScale
      const renderedLeft = (cropViewportSize - renderedW) / 2 + cropOffsetX.value
      const renderedTop = (cropViewportSize - renderedH) / 2 + cropOffsetY.value

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
        const fileName = currentCropFile.value?.name || `image-${Date.now()}.jpg`
        resolve(new File([blob], fileName, { type: 'image/jpeg' }))
      }, 'image/jpeg', 0.92)
    }
    image.onerror = () => resolve(null)
    image.src = cropImageUrl.value
  })
}

const applyCropAndNext = async () => {
  const cropped = await createCroppedFile()
  finalizeCurrentCrop(cropped || currentCropFile.value!)
}

const skipCurrentCrop = () => {
  if (!currentCropFile.value) return
  finalizeCurrentCrop(currentCropFile.value)
}

const handleImageSelect = (event: any) => {
  const incomingFiles = Array.from(event.files || []) as File[]
  if (!incomingFiles.length) return

  cropQueue.value = incomingFiles
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

  if (
    isEditMode.value &&
    form.value.base_price != null &&
    originalBasePrice.value !== form.value.base_price &&
    !form.value.price_change_reason
  ) {
    errors.value.price_change_reason = 'Price change reason is required'
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
      product_type: isRawMaterialForm.value ? 'raw_material' : form.value.product_type,
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
      const formData = new FormData()
      formData.append('product_id', productId.toString())
      formData.append('asset_type', i === 0 ? 'Image_Main' : 'Image_Gallery')
      formData.append('asset_file', form.value.imageFiles[i]) // Note: asset_file
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

const menuItems = computed(() => {
  const base = [
    { key: 'product', label: 'Product Information', icon: 'pi pi-info-circle' },
    { key: 'pricing', label: 'Pricing', icon: 'pi pi-dollar' },
    { key: 'assets', label: 'Assets', icon: 'pi pi-box' },
  ]
  if (!isRawMaterialForm.value) {
    base.push({ key: 'seo', label: 'SEO', icon: 'pi pi-search' })
  }
  return base
})

const currentSection = computed(() => {
  const sections = {
    product: { title: 'Product Information', description: 'Basic details about your product' },
    pricing: { title: 'Pricing', description: 'Set the price and tax information' },
    assets: { title: 'Assets', description: 'Upload 3D models and product images' },
    seo: { title: 'SEO & Metadata', description: 'Optimize for search engines' }
  }
  return sections[activeSection.value]
})

onMounted(() => {
  if (!isEditMode.value && isRawMaterialForm.value) {
    form.value.product_type = 'raw_material'
    form.value.discounted_price = null
    form.value.is_featured = false
    form.value.is_new_arrival = false
    form.value.is_bestseller = false
  }
  loadCategories()
  loadProduct()
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
