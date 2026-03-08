import axios from 'axios'

const API_BASE = process.env.VUE_APP_API_BASE || 'http://localhost:8000/api'

export const productService = {
  // Get dropdown data
  async getDropdownData() {
    try {
      const response = await axios.get(`${API_BASE}/product/dropdown-data`)
      return response.data
    } catch (error) {
      throw new Error('Failed to fetch dropdown data')
    }
  },

  // Save as draft
  async saveAsDraft(productData) {
    try {
      const response = await axios.post(`${API_BASE}/product/draft`, productData)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to save draft')
    }
  },

  // Upload 3D files
  async upload3DFiles(files, progressCallback) {
    const formData = new FormData()
    
    if (files.webGLB) {
      formData.append('web_glb', files.webGLB)
    }
    if (files.mobileGLB) {
      formData.append('mobile_glb', files.mobileGLB)
    }
    if (files.iosUSDZ) {
      formData.append('ios_usdz', files.iosUSDZ)
    }
    
    formData.append('model_scale', files.modelScale)
    formData.append('pivot_point', files.pivotPoint)
    formData.append('lighting_preset', files.lightingPreset)

    try {
      const response = await axios.post(`${API_BASE}/product/upload-3d`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          if (progressCallback) {
            const progress = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            )
            progressCallback(progress)
          }
        }
      })
      
      return response.data.files
    } catch (error) {
      throw new Error('Failed to upload 3D files')
    }
  },

  // Upload images
  async uploadImages(images, progressCallback) {
    const formData = new FormData()
    
    if (images.thumbnail) {
      formData.append('thumbnail', images.thumbnail)
    }
    
    if (images.arPreview) {
      formData.append('ar_preview', images.arPreview)
    }
    
    images.gallery.forEach((file, index) => {
      formData.append(`gallery[${index}]`, file)
    })
    
    images.roomScenes.forEach((file, index) => {
      formData.append(`room_scenes[${index}]`, file)
    })

    try {
      const response = await axios.post(`${API_BASE}/product/upload-images`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          if (progressCallback) {
            const progress = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            )
            progressCallback(progress)
          }
        }
      })
      
      return response.data.images
    } catch (error) {
      throw new Error('Failed to upload images')
    }
  },

  // Submit complete product
  async submitProduct(productData) {
    try {
      const response = await axios.post(`${API_BASE}/product`, productData)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to submit product')
    }
  },

  // Validate SKU uniqueness
  async validateSKU(sku) {
    try {
      const response = await axios.get(`${API_BASE}/product/validate-sku/${sku}`)
      return response.data.available
    } catch (error) {
      throw new Error('Failed to validate SKU')
    }
  },

  // Get product by ID
  async getProduct(id) {
    try {
      const response = await axios.get(`${API_BASE}/product/${id}`)
      return response.data
    } catch (error) {
      throw new Error('Failed to fetch product')
    }
  },

  // Update product
  async updateProduct(id, productData) {
    try {
      const response = await axios.put(`${API_BASE}/product/${id}`, productData)
      return response.data
    } catch (error) {
      throw new Error('Failed to update product')
    }
  },

  // Delete product
  async deleteProduct(id) {
    try {
      const response = await axios.delete(`${API_BASE}/product/${id}`)
      return response.data
    } catch (error) {
      throw new Error('Failed to delete product')
    }
  }
}