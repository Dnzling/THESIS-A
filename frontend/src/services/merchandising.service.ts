import axiosClient from '../axios'

export interface Product {
    id?: number
    sku: string
    product_name: string
    description?: string
    category_id: number
    subcategory_id?: number
    brand?: string
    collection_name?: string
    base_price: number
    discounted_price?: number
    tax_rate?: number
    length_cm?: number
    width_cm?: number
    height_cm?: number
    weight_kg?: number
    assembly_required?: boolean
    is_featured?: boolean
    is_new_arrival?: boolean
    is_bestseller?: boolean
    is_active?: boolean
    stock_status: string
    meta_title?: string
    meta_description?: string
    published_at?: string
}

export interface Category {
    id?: number
    category_code: string
    category_name: string
    description?: string
    parent_category_id?: number
    icon_path?: string
    is_active?: boolean
    display_order?: number
}

export interface Tag {
    id?: number
    tag_name: string
    tag_type: 'Style' | 'Room' | 'Promotion' | 'Feature'
    is_active?: boolean
}

export interface Attribute {
    id?: number
    attribute_name: string
    attribute_type: 'Text' | 'Number' | 'Select' | 'Color' | 'Multi-select'
    is_filterable?: boolean
    display_order?: number
}

export interface ProductVariation {
    id?: number
    product_id: number
    variation_sku: string
    variation_name: string
    color?: string
    color_hex?: string
    size?: string
    material?: string
    price_adjustment: number
    custom_3d_model_id?: number
    is_active?: boolean
}

export interface ProductAsset {
    id?: number
    product_id: number
    asset_type: string
    file_name: string
    is_primary?: boolean
    display_order?: number
}

class MerchandisingService {
    private baseUrl = '/api/product-catalog'

    async getDashboardStats() {
        const response = await axiosClient.get(`${this.baseUrl}/dashboard/stats`)
        return response.data
    }
    async getActivityLog(params: any) {
        const response = await axiosClient.get(`${this.baseUrl}/dashboard/activity`, { params })
        return response.data
    }

    // ==================== PRODUCTS ====================
    async getProducts(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/products`, { params })
        return response.data
    }

    async getProduct(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/products/${id}`)
        return response.data
    }

    async createProduct(data: Product) {
        const response = await axiosClient.post(`${this.baseUrl}/products`, data)
        return response.data
    }

    async updateProduct(id: number, data: Partial<Product>) {
        const response = await axiosClient.put(`${this.baseUrl}/products/${id}`, data)
        return response.data
    }

    async deleteProduct(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/products/${id}`)
        return response.data
    }

    async bulkStatusUpdate(product_ids: number[], is_active: boolean) {
        const response = await axiosClient.post(`${this.baseUrl}/products/bulk/status`, {
            product_ids,
            is_active
        })
        return response.data
    }

    async get3DData(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/products/${id}/3d-data`)
        return response.data
    }

    // ==================== CATEGORIES ====================
    async getCategories(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/categories`, { params })
        return response.data
    }

    async getCategory(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/categories/${id}`)
        return response.data
    }

    async getCategoryTree() {
        const response = await axiosClient.get(`${this.baseUrl}/categories/tree/all`)
        return response.data
    }


    async createCategory(data: Category) {
        const response = await axiosClient.post(`${this.baseUrl}/categories`, data)
        return response.data
    }

    async updateCategory(id: number, data: Partial<Category>) {
        const response = await axiosClient.put(`${this.baseUrl}/categories/${id}`, data)
        return response.data
    }

    async deleteCategory(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/categories/${id}`)
        return response.data
    }

    // ==================== TAGS ====================
    async getTags(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/tags`, { params })
        return response.data
    }

    async getTag(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/tags/${id}`)
        return response.data
    }

    async createTag(data: Tag) {
        const response = await axiosClient.post(`${this.baseUrl}/tags`, data)
        return response.data
    }

    async updateTag(id: number, data: Partial<Tag>) {
        const response = await axiosClient.put(`${this.baseUrl}/tags/${id}`, data)
        return response.data
    }

    async deleteTag(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/tags/${id}`)
        return response.data
    }

    async assignTagsToProduct(product_id: number, tag_ids: number[]) {
        const response = await axiosClient.post(`${this.baseUrl}/tags/assign-to-product`, {
            product_id,
            tag_ids
        })
        return response.data
    }

    // ==================== ATTRIBUTES ====================
    async getAttributes(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/attributes`, { params })
        return response.data
    }

    async getAttribute(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/attributes/${id}`)
        return response.data
    }

    async createAttribute(data: Attribute) {
        const response = await axiosClient.post(`${this.baseUrl}/attributes`, data)
        return response.data
    }

    async updateAttribute(id: number, data: Partial<Attribute>) {
        const response = await axiosClient.put(`${this.baseUrl}/attributes/${id}`, data)
        return response.data
    }

    async deleteAttribute(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/attributes/${id}`)
        return response.data
    }

    // ==================== VARIATIONS ====================
    async getVariations(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/variations`, { params })
        return response.data
    }

    async getVariation(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/variations/${id}`)
        return response.data
    }

    async getVariationsByProduct(productId: number) {
        const response = await axiosClient.get(`${this.baseUrl}/products/${productId}/variations`)
        return response.data
    }

    async createVariation(data: ProductVariation) {
        const response = await axiosClient.post(`${this.baseUrl}/variations`, data)
        return response.data
    }

    async updateVariation(id: number, data: Partial<ProductVariation>) {
        const response = await axiosClient.put(`${this.baseUrl}/variations/${id}`, data)
        return response.data
    }

    async deleteVariation(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/variations/${id}`)
        return response.data
    }

    async bulkUpdateStock(variations: Array<{ id: number; stock_quantity: number }>) {
        const response = await axiosClient.post(`${this.baseUrl}/variations/bulk/stock`, {
            variations
        })
        return response.data
    }

    // ==================== ASSETS ====================
    async getAssets(params?: any) {
        const response = await axiosClient.get(`${this.baseUrl}/assets`, { params })
        return response.data
    }

    async getAsset(id: number) {
        const response = await axiosClient.get(`${this.baseUrl}/assets/${id}`)
        return response.data
    }

    async getAssetsByProduct(productId: number) {
        const response = await axiosClient.get(`${this.baseUrl}/assets/product/${productId}`)
        return response.data
    }

    async uploadAsset(formData: FormData) {
        const response = await axiosClient.post(`${this.baseUrl}/assets/upload`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        return response.data
    }

    async updateAsset(id: number, data: Partial<ProductAsset>) {
        const response = await axiosClient.put(`${this.baseUrl}/assets/${id}`, data)
        return response.data
    }

    async deleteAsset(id: number) {
        const response = await axiosClient.delete(`${this.baseUrl}/assets/${id}`)
        return response.data
    }

    async reorderAssets(product_id: number, assets: Array<{ id: number; display_order: number }>) {
        const response = await axiosClient.post(`${this.baseUrl}/assets/reorder`, {
            product_id,
            assets
        })
        return response.data
    }
}

export default new MerchandisingService()