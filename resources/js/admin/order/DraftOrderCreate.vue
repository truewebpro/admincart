<template>
    <v-container class="pa-1 pa-lg-3">
        <v-row dense>
            <v-col cols="12" md="6">
                <div class="d-flex align-center ga-1">
                    <v-icon @click="goBack">mdi-chevron-double-left</v-icon>
                    <h2>{{ draftOrder.id ? draftOrder.draft_number : 'Create Draft Order' }}</h2>
                    <div class="muted">{{ saveStatusLabel }}</div>
                </div>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn variant="outlined" size="small" class="mr-2" @click="saveDraft" :loading="saving">
                    Save draft
                </v-btn>
            </v-col>
        </v-row>
        <v-row dense v-if="draftOrder.converted_order_id">
            <v-col cols="12" md="12">
                <v-card class="section-card">
                    <v-card-title class="bg-success">{{ draftOrder.status }}</v-card-title>
                    <v-card-text class="pt-4 text-body-1">
                        Order created on {{dayjs(draftOrder.confirmed_at).format('DD MMM YYYY, h:mm a')}}. You can
                        <router-link :to="'/orders/'+draftOrder.converted_order_id">view the order</router-link>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-alert v-if="loadError" type="error" class="mb-4" variant="tonal">
            {{ loadError }}
        </v-alert>
        <v-row>
            <v-col cols="12" md="8">
                <v-card class="section-card mb-4">
                    <div class="card-header-row">
                        <span class="card-header-title">Products</span>
                        <div>
                            <v-btn size="small" variant="outlined" class="mr-2" @click="openProductDialog">
                                <v-icon size="15" class="mr-1">mdi-plus</v-icon> Add product
                            </v-btn>
<!--                            <v-btn size="small" variant="text" :href="createProductUrl" target="_blank">-->
<!--                                Can't find it? Create a new product-->
<!--                            </v-btn>-->
                        </div>
                    </div>
                    <div v-if="items.length === 0" class="empty-state">
                        <v-icon size="40" color="grey-lighten-1">mdi-package-variant</v-icon>
                        <div class="muted mt-2">No products added yet</div>
                        <v-btn size="small" class="mt-3" variant="tonal" @click="openProductDialog">Add product</v-btn>
                    </div>
                    <div v-else class="items-table">
                        <v-data-table
                            :items="items"
                            :headers="itemsHeader"
                            density="comfortable"
                            items-per-page="-1"
                            hide-default-footer
                            mobile-breakpoint="sm"
                        >
                            <template #item.product="{ item }">
                                <div class="product-cell py-1">
                                    <div class="product-thumb">
                                        <v-img v-if="item.product_image" :src="`${cdn}${item.product_image}`" cover></v-img>
                                        <v-icon v-else size="18" color="grey">mdi-image-outline</v-icon>
                                    </div>
                                    <div>
                                        <div class="product-title text-caption text-lg-body-2">{{ item.title }}</div>
                                        <v-chip v-if="item.variant_title" size="x-small" density="comfortable" class="mt-1">
                                            {{ item.variant_title }}
                                        </v-chip>
                                        <div class="muted text-caption" v-if="item.sku">SKU: {{ item.sku }}</div>
                                        <div class="chip-row">
                                            <v-chip
                                                v-if="item.current_stock !== null && item.current_stock < item.quantity && item.current_stock > 0"
                                                color="error" size="x-small" class="warn-chip"
                                            >
                                                Insufficient stock ({{ item.current_stock }} available)
                                            </v-chip>
                                            <v-chip v-if="item.current_stock === 0" color="error" size="x-small" class="warn-chip">
                                                Out of stock
                                            </v-chip>
                                            <v-chip v-if="item.is_below_cost" color="warning" size="x-small" class="warn-chip">
                                                Below cost
                                            </v-chip>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template #item.quantity="{ item }">
                                <v-number-input
                                    :model-value="item.quantity"
                                    @update:model-value="val => updateItemField(item, 'quantity', Number(val))"
                                    control-variant="stacked"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    :min="1"
                                    class="qty-number-input"
                                ></v-number-input>
                            </template>

                            <template #item.unit_price="{ item }">
                                <v-number-input
                                    :model-value="item.unit_price"
                                    @update:model-value="val => updateItemField(item, 'unit_price', Number(val))"
                                    control-variant="stacked"
                                    density="compact"
                                    variant="outlined"
                                    hide-details
                                    prefix="£"
                                    :precision="2"
                                    :min="0.00"
                                    class="price-number-input"
                                ></v-number-input>
                            </template>

                            <template #item.total="{ item }">
                                <div class="line-total">£{{Number(item.unit_price)*Number(item.quantity).toFixed(2)}}</div>
<!--                                <div class="line-total">£{{ Number(item.line_total).toFixed(2) }}</div>-->
                            </template>

                            <template #item.actions="{ item }">
                                <button type="button" class="row-delete-btn" @click="removeItem(item)" aria-label="Remove item">
                                    <v-icon size="17">mdi-trash-can-outline</v-icon>
                                </button>
                            </template>
                        </v-data-table>
                    </div>
                </v-card>
                <v-card class="section-card">
                    <div class="card-header-row">
                        <span class="card-header-title">Payment</span>
                    </div>
                    <div class="pa-4">
                        <div class="totals-row">
                            <span class="muted">Subtotal ({{ items.length }} item{{ items.length === 1 ? '' : 's' }})</span>
                            <span>£{{ Number(draftOrder.subtotal || 0).toFixed(2) }}</span>
                        </div>
                        <!-- ORDER DISCOUNT -->
                        <div class="adjustment-block">
                            <div class="totals-row">
                                <span class="muted font-weight-semibold">
                                  Order discount
                                </span>
                                <div>
                                    <span v-if="draftOrder.discount_value > 0" class="muted">(-£{{ Number(draftOrder.discount_total || 0).toFixed(2) }})</span>
                                    <v-btn v-if="!draftOrder.discount_value" variant="text" size="small" color="primary" @click="discountPanelOpen = !discountPanelOpen">
                                        <v-icon size="16" class="mr-1">mdi-plus</v-icon> Add discount
                                    </v-btn>
                                    <v-btn v-else variant="text" size="small" @click="discountPanelOpen = !discountPanelOpen">
                                        <v-icon size="16">mdi-pencil-outline</v-icon>
                                    </v-btn>
                                </div>
                            </div>
                            <v-expand-transition>
                                <div v-if="discountPanelOpen" class="adjustment-panel">
                                    <v-radio-group v-model="discountForm.type" inline density="comfortable" hide-details class="mb-2">
                                        <v-radio label="Percentage (%)" value="percentage"></v-radio>
                                        <v-radio label="Fixed amount (£)" value="fixed"></v-radio>
                                    </v-radio-group>
                                    <v-number-input
                                        v-model="discountForm.value"
                                        :label="discountForm.type === 'percentage' ? 'Discount percentage' : 'Discount amount'"
                                        :precision="discountForm.type === 'percentage' ? 0 : 2"
                                        density="compact" variant="outlined" type="number" :min="0" hide-details class="mb-2"
                                    ></v-number-input>
                                    <div class="d-flex justify-end ga-4">
                                        <v-btn size="small" variant="text" @click="removeDiscount">Remove</v-btn>
                                        <v-btn size="small" color="black" @click="applyDiscount">Done</v-btn>
                                    </div>
                                </div>
                            </v-expand-transition>
                        </div>
                        <!-- DELIVERY -->
                        <div class="adjustment-block">
                            <div class="totals-row">
                                <span class="muted font-weight-semibold">Delivery</span>
                                <div class="d-flex align-center">
                                    <v-chip v-if="deliveryConfirmed && draftOrder.shipping_total === 0" size="x-small" color="success" variant="tonal" class="mr-1">
                                        Free delivery
                                    </v-chip>
                                    <span v-else-if="deliveryConfirmed" class="d-flex ga-2">
                                        <span v-if="draftOrder.courier">{{draftOrder.courier}}</span>
                                        <span v-if="draftOrder.shipping_method" class="muted">{{ draftOrder.shipping_method }}</span>
                                        <span>£{{ Number(draftOrder.shipping_total || 0).toFixed(2) }}</span>
                                    </span>
                                    <v-btn v-if="!deliveryConfirmed" variant="text" size="small" color="primary" @click="openDeliveryPanel">
                                        <v-icon size="16" class="mr-1">mdi-plus</v-icon> Add delivery charge
                                    </v-btn>
                                    <v-btn v-else variant="text" size="small" @click="openDeliveryPanel">
                                        <v-icon size="16">mdi-pencil-outline</v-icon>
                                    </v-btn>
                                </div>
                            </div>
                            <v-expand-transition>
                                <div v-if="deliveryPanelOpen" class="adjustment-panel">
                                    <v-select
                                        v-model="shippingForm.ship_method_id"
                                        :items="shipMethods"
                                        item-title="method"
                                        item-value="ship_method_id"
                                        label="Shipping method"
                                        density="compact" variant="outlined" hide-details class="mb-2"
                                        @update:model-value="onShipMethodSelected"
                                    >
                                        <template #item="{ props, item }">
                                            <v-list-item
                                                v-bind="props"
                                                :subtitle="`${item.raw.courier_name || 'No courier'} — £${Number(item.raw.price).toFixed(2)}`"
                                            ></v-list-item>
                                        </template>
                                    </v-select>
                                    <v-number-input
                                        v-model="shippingForm.shipping_total" :precision="2"
                                        label="Delivery charge" prefix="£" :min="0"
                                        density="compact" variant="outlined" hide-details class="mb-2"
                                    ></v-number-input>
                                    <div class="d-flex justify-end ga-4">
                                        <v-btn size="small" color="black" @click="confirmShipping">Done</v-btn>
                                    </div>
                                </div>
                            </v-expand-transition>
                        </div>
                        <div class="totals-row">
                            <span class="muted">{{ draftOrder.vat_included ? 'VAT (included)' : 'VAT' }}</span>
                            <span>£{{ Number(draftOrder.tax_total || 0).toFixed(2) }}</span>
                        </div>

                        <div class="totals-row grand">
                            <span>Total</span>
                            <span>£{{ Number(draftOrder.total || 0).toFixed(2) }}</span>
                        </div>
                        <v-divider class="my-3"></v-divider>
                        <div class="totals-row">
                            <span class="muted">Amount paid</span>
                            <span>£{{ Number(draftOrder.amount_paid || 0).toFixed(2) }}</span>
                        </div>
                        <div class="totals-row" :style="{ color: draftOrder.amount_due > 0 ? '#c0392b' : '#2e7d32' }">
                            <span>Amount due</span>
                            <span>£{{ Number(draftOrder.amount_due || 0).toFixed(2) }}</span>
                        </div>

                    </div>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn variant="outlined" size="small" @click="recordPaymentDialog = true" :disabled="isConfirmed || !draftOrder.id || !draftOrder.shipping_method">
                            Record payment
                        </v-btn>
                        <v-btn variant="elevated" size="small" color="success"
                               :disabled="items.length === 0 || isConfirmed || !draftOrder.shipping_address_id || !draftOrder.shipping_method"
                               @click="openCreateOrderDialog">
                            {{ isConfirmed ? 'Order created' : 'Create order' }}
                        </v-btn>
                    </v-card-actions>
                    <v-card-text v-if="!isConfirmed && items.length > 0 && !draftOrder.shipping_address_id" class="muted mt-1 text-center text-body-2">
                        Add a delivery address before creating the order
                    </v-card-text>
                    <v-card-text v-if="draftOrder.payments && draftOrder.payments.length">
                        <v-divider class="mb-3" variant="double" thickness="3"></v-divider>
                        <div class="muted mb-2 font-weight-semibold text-body-2 text-uppercase">
                            Payment history
                        </div>
                        <div v-for="payment in draftOrder.payments" :key="payment.id">
                            <div class="d-flex justify-space-between align-center border-t pb-2 pt-2">
                                <div>
                                    <div class="text-body-1 font-weight-medium">
                                        £{{ Number(payment.amount).toFixed(2) }}
                                        <span class="muted font-weight-regular">— {{ paymentMethodLabel(payment.payment_method) }}</span>
                                    </div>
                                    <div class="muted text-body-2">
                                        {{ formatDateTime(payment.paid_at || payment.created_at) }}
                                        <template v-if="payment.payment_reference"> · Ref: {{ payment.payment_reference }}</template>
                                    </div>
                                    <div v-if="payment.notes" class="muted text-body-2 font-italic">
                                        {{ payment.notes }}
                                    </div>
                                </div>
                                <v-chip size="small" :color="paymentRecordStatusColor(payment.payment_status)" variant="tonal">
                                    {{ payment.payment_status }}
                                </v-chip>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="section-card sidebar-card">
                    <div class="card-header-row">
                        <span class="card-header-title">Customer</span>
                        <v-btn v-if="draftOrder.customer" icon size="x-small" variant="text" @click="removeCustomer">
                            <v-icon size="16">mdi-account-minus</v-icon>
                        </v-btn>
                    </div>
                    <v-card-text>
                        <div v-if="!draftOrder.customer">
                            <v-autocomplete
                                v-model="customerSearchSelection"
                                :items="customerResults"
                                item-title="name"
                                item-value="customer_id"
                                :loading="customerSearchLoading"
                                density="compact" variant="outlined"
                                placeholder="Search by name, email, or postcode"
                                hide-details clearable no-filter
                                @update:search="searchCustomers"
                                @update:model-value="selectCustomer"
                                @click:clear="customerResults = []"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item density="compact"
                                                 v-bind="props"
                                                 :subtitle="item.raw.email"
                                                 :title="item.raw.name"
                                    ></v-list-item>
                                </template>
                            </v-autocomplete>
                            <v-btn variant="outlined" density="comfortable" size="small" class="mt-2" @click="customerCreateDialog = true">
                                <v-icon size="16" class="mr-1">mdi-plus</v-icon> Create new customer
                            </v-btn>
                        </div>
                        <div v-else>
                            <div style="font-weight:600; font-size:13.5px;">{{ draftOrder.customer.name }}</div>
                            <div class="muted">{{ draftOrder.customer.email }}</div>
                            <div class="muted">{{ draftOrder.customer.phone }}</div>
                            <template v-if="draftOrder.customer.business_profile">
                                <v-divider class="my-2"></v-divider>
                                <div class="muted">Credit limit: £{{ Number(draftOrder.customer.business_profile.credit_limit).toFixed(2) }}</div>
                                <div class="muted">Available credit: £{{ Number(draftOrder.customer.business_profile.available_credit).toFixed(2) }}</div>
                            </template>
                            <v-divider class="my-2"></v-divider>
                            <div class="muted mb-1 font-weight-semibold text-caption text-uppercase">
                                Delivery address
                            </div>
                            <div v-if="draftOrder.shipping_address_id" class="text-body-2">
                                <div>{{ draftOrder.shipping_address.address_line1 }}</div>
                                <div v-if="draftOrder.shipping_address.address_line2">{{ draftOrder.shipping_address.address_line2 }}</div>
                                <div>{{ draftOrder.shipping_address.city }}, {{ draftOrder.shipping_address.postcode }}</div>
                                <v-btn variant="outlined" size="small" density="comfortable" class="mt-1"
                                       @click="openAddressDialog">
                                    Change address
                                </v-btn>
                            </div>
                            <div v-else>
                                <v-alert type="warning" variant="tonal" density="compact" class="mb-2">
                                    No delivery address set — required before this can be converted to a real order.
                                </v-alert>
                                <v-btn variant="tonal" size="small" @click="openAddressDialog">Add delivery address</v-btn>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
                <!-- NOTES CARD -->
                <v-card class="section-card sidebar-card">
                    <div class="card-header-row"><span class="card-header-title">Notes</span></div>
                    <div class="pa-4">
                        <v-textarea
                            v-model="notesForm.internal_note" label="Internal note" density="compact" variant="outlined"
                            rows="2" hide-details class="mb-3" @blur="saveNotes"
                        ></v-textarea>
                        <v-textarea
                            v-model="notesForm.customer_note" label="Customer-visible note" density="compact" variant="outlined"
                            rows="2" hide-details @blur="saveNotes"
                        ></v-textarea>
                    </div>
                </v-card>
                <!-- PAYMENT STATUS CARD -->
                <v-card class="section-card sidebar-card">
                    <div class="card-header-row"><span class="card-header-title">Payment status</span></div>
                    <div class="pa-4">
                        <v-chip :color="paymentStatusColor" size="small">{{ paymentStatusLabel }}</v-chip>
                    </div>
                </v-card>
                <!-- ORDER STATUS CARD -->
                <v-card class="section-card sidebar-card">
                    <div class="card-header-row"><span class="card-header-title">Order status</span></div>
                    <div class="pa-4">
                        <v-chip :color="isConfirmed ? 'primary' : 'grey-darken-1'" size="small" variant="tonal">
                            {{ draftOrder.status || 'draft' }}
                        </v-chip>
                    </div>
                </v-card>
                <!-- TAGS CARD -->
                <v-card class="section-card sidebar-card">
                    <div class="card-header-row"><span class="card-header-title">Draft Order Tags</span></div>
                    <div class="pa-4">
                        <v-combobox
                            v-model="selectedTagNames"
                            :items="availableTagNames"
                            multiple chips closable-chips
                            density="compact" variant="outlined" hide-details
                            placeholder="Add tags"
                            @update:model-value="onTagsChanged"
                        ></v-combobox>
                    </div>
                </v-card>

            </v-col>
        </v-row>

        <!-- PRODUCT SEARCH DIALOG -->
        <v-dialog v-model="productDialog" max-width="900">
            <v-card>
                <v-card-item title="Add products" class="px-3 py-1">
                    <template #append>
                        <v-btn icon variant="text" size="small" @click="productDialog = false"><v-icon>mdi-close</v-icon></v-btn>
                    </template>
                </v-card-item>
                <v-divider></v-divider>
                <v-card-text class="pa-2">
                    <v-text-field
                        v-model="productSearchQuery" density="compact" variant="outlined"
                        prepend-inner-icon="mdi-magnify" placeholder="Search products"
                        hide-details class="mb-1"
                        @update:model-value="debouncedProductSearch"
                    ></v-text-field>
                    <v-skeleton-loader v-if="productSearchLoading" type="table-row@4"></v-skeleton-loader>
                    <v-table v-else density="compact">
                        <thead class="bg-grey-lighten-4">
                        <tr>
                            <th></th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th style="width:100px;">Qty</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="p in productResults" :key="p.variant_id">
                            <td><v-checkbox-btn v-model="p.selected"></v-checkbox-btn></td>
                            <td>
                                <v-img :src="`${cdn}${p.image || 'noimage.png'}`"></v-img>
                            </td>
                            <td>
                                <div style="font-size:13px; font-weight:500;">{{ p.title }}</div>
                                <v-chip size="x-small" variant="outlined" color="primary" class="muted text-body-2">{{ p.variant_title }}</v-chip>
                            </td>
                            <td class="muted">{{ p.sku }}</td>
                            <td>
                                <span :style="{ color: p.stock === 0 ? '#c0392b' : (p.stock < 10 ? '#b8860b' : '#2e7d32') }">{{ p.stock }}</span>
                            </td>
                            <td>£{{ Number(p.price).toFixed(2) }}</td>
                            <td class="py-1" width="130">
                                <v-number-input v-model="p.qty" :min="0" density="compact" variant="outlined" hide-details style="max-width:150px;" controlVariant="stacked"></v-number-input>
                            </td>
                        </tr>
                        <tr v-if="!productSearchLoading && productResults.length === 0">
                            <td colspan="6" class="text-center muted py-4">No products found</td>
                        </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4 position-sticky bottom-0 bg-white">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="productDialog = false">Cancel</v-btn>
                    <v-btn variant="elevated" color="black" size="small" @click="addSelectedProducts">Add selected products</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- CUSTOMER CREATE DIALOG -->
        <v-dialog v-model="customerCreateDialog" max-width="480">
            <v-card>
                <v-card-title class="py-3 px-4">Create customer</v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-text-field v-model="newCustomer.fname" label="First name" density="compact" variant="outlined" class="mb-3" hide-details></v-text-field>
                    <v-text-field v-model="newCustomer.lname" label="Last name" density="compact" variant="outlined" class="mb-3" hide-details></v-text-field>
                    <v-text-field v-model="newCustomer.email" label="Email" density="compact" variant="outlined" class="mb-3" hide-details></v-text-field>
                    <v-text-field v-model="newCustomer.phone" label="Phone" density="compact" variant="outlined" hide-details></v-text-field>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="customerCreateDialog = false">Cancel</v-btn>
                    <v-btn color="black" size="small" :loading="creatingCustomer" @click="createCustomer">Create</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- ADDRESS DIALOG -->
        <v-dialog v-model="addressDialog" max-width="480">
            <v-card>
                <v-card-title class="py-3 px-4">Delivery address</v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-skeleton-loader v-if="addressesLoading" type="list-item-two-line@2"></v-skeleton-loader>
                    <div v-else>
                        <div v-if="customerAddresses.length === 0" class="muted py-2">
                            No saved addresses for this customer yet.
                        </div>
                        <v-radio-group v-else v-model="selectedAddressId" hide-details>
                            <v-radio v-for="addr in customerAddresses" :key="addr.address_id" :value="addr.address_id">
                                <template #label>
                                    <div>
                                        <div style="font-size:13px; font-weight:500;">
                                            {{ addr.name }}
                                            <v-chip v-if="addr.is_default" size="x-small" variant="tonal" class="ml-1">Default</v-chip>
                                        </div>
                                        <div class="muted" style="font-size:12px;">
                                            {{ addr.address_line1 }}<span v-if="addr.address_line2">, {{ addr.address_line2 }}</span>, {{ addr.city }}, {{ addr.postcode }}
                                        </div>
                                    </div>
                                </template>
                            </v-radio>
                        </v-radio-group>
                    </div>

                    <v-btn variant="text" size="small" class="mt-2 pa-0" @click="showNewAddressForm = !showNewAddressForm">
                        <v-icon size="16" class="mr-1">mdi-plus</v-icon> Add new address
                    </v-btn>

                    <v-expand-transition>
                        <div v-if="showNewAddressForm" class="adjustment-panel mt-2">
                            <v-row dense>
                                <v-col cols="6"><v-text-field v-model="newAddress.fname" label="First name" density="compact" variant="outlined" hide-details></v-text-field></v-col>
                                <v-col cols="6"><v-text-field v-model="newAddress.lname" label="Last name" density="compact" variant="outlined" hide-details></v-text-field></v-col>
                            </v-row>
                            <v-text-field v-model="newAddress.address_line1" label="Address line 1" density="compact" variant="outlined" hide-details class="mt-2"></v-text-field>
                            <v-text-field v-model="newAddress.address_line2" label="Address line 2 (optional)" density="compact" variant="outlined" hide-details class="mt-2"></v-text-field>
                            <v-row dense class="mt-1">
                                <v-col cols="7"><v-text-field v-model="newAddress.city" label="City" density="compact" variant="outlined" hide-details></v-text-field></v-col>
                                <v-col cols="5"><v-text-field v-model="newAddress.postcode" label="Postcode" density="compact" variant="outlined" hide-details></v-text-field></v-col>
                            </v-row>
                            <v-text-field v-model="newAddress.phone" label="Phone (optional)" density="compact" variant="outlined" hide-details class="mt-2"></v-text-field>
                            <div class="d-flex justify-end mt-2">
                                <v-btn size="small" color="black" :loading="savingNewAddress" @click="createAndSelectAddress">Save address</v-btn>
                            </div>
                        </div>
                    </v-expand-transition>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="addressDialog = false">Cancel</v-btn>
                    <v-btn color="black" size="small" :disabled="!selectedAddressId" :loading="settingAddress" @click="confirmAddressSelection">
                        Use this address
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- RECORD PAYMENT DIALOG -->
        <v-dialog v-model="recordPaymentDialog" max-width="360">
            <v-card>
                <v-card-title class="py-3 px-4">Record payment</v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-number-input v-model="newPayment.amount" label="Amount" prefix="£"
                                    :precision="2" :min="0.00"
                                    density="compact" variant="outlined" class="mb-3"
                                    hide-details></v-number-input>
                    <v-select
                        v-model="newPayment.payment_method"
                        :items="[
                          { title: 'Cash', value: 'cash' },
                          { title: 'Card', value: 'card' },
                          { title: 'Bank transfer', value: 'bank_transfer' },
                          { title: 'Customer credit', value: 'customer_credit' },
                          { title: 'Other', value: 'other' },
                        ]"
                        label="Payment method" density="compact" variant="outlined" class="mb-3" hide-details
                    ></v-select>
                    <v-text-field
                        v-model="newPayment.payment_reference"
                        label="Reference (optional)"
                        placeholder="e.g. transaction ID, cheque number"
                        density="compact" variant="outlined" hide-details class="mb-3"
                    ></v-text-field>
                    <v-textarea
                        v-model="newPayment.notes"
                        label="Notes (optional)"
                        density="compact" variant="outlined" rows="2" hide-details
                    ></v-textarea>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="recordPaymentDialog = false">Cancel</v-btn>
                    <v-btn color="black" size="small" :loading="recordingPayment" @click="recordPayment">Record payment</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- CREATE ORDER CONFIRMATION DIALOG -->
        <v-dialog v-model="createOrderDialog" max-width="420">
            <v-card>
                <v-card-title class="py-3 px-4">Create order</v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <div class="totals-row"><span class="muted">Items</span><span>{{ items.length }}</span></div>
                    <div class="totals-row grand" style="border-top:none; padding-top:0;">
                        <span>Total due</span><span>£{{ Number(draftOrder.total || 0).toFixed(2) }}</span>
                    </div>
                    <v-alert v-if="stockShortages.length" type="error" variant="tonal" density="compact" class="mt-3">
                        <div v-for="s in stockShortages" :key="s.variant_id">
                            {{ s.title }} — requested {{ s.requested }}, {{ s.available }} available
                        </div>
                    </v-alert>
                    <v-checkbox
                        v-if="stockShortages.length"
                        v-model="overrideStock" density="comfortable" hide-details
                        label="Create order anyway (override stock check)"
                        class="mt-1"
                    ></v-checkbox>
                    <v-divider class="my-3"></v-divider>
                    <v-checkbox
                        v-model="sendInvoiceOnCreate" density="comfortable" hide-details
                        :label="draftOrder.customer ? `Send invoice to ${draftOrder.customer.email}` : 'Send invoice to customer'"
                        :disabled="!draftOrder.customer"
                    ></v-checkbox>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="createOrderDialog = false">Cancel</v-btn>
                    <v-btn color="black" size="small" :loading="convertingOrder" @click="convertToOrder">Create order</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</v-snackbar>
    </v-container>
</template>
<script>
import dayjs from "dayjs";

const BASE_URL = '/sadmin/draft-orders';
export default {
    name: "DraftOrderCreate",
    data() {
        return {
            cdn: this.$store.state.cdn,
            draftOrder: { items: [], tags: [] },
            itemsHeader: [
                { title: 'Product', key: 'product', sortable: false },
                { title: 'Quantity', key: 'quantity', sortable: false, align: 'center', width: '130px' },
                { title: 'Price per item', key: 'unit_price', sortable: false, width: '160px' },
                { title: 'Total', key: 'total', sortable: false, align: 'end', width: '90px' },
                { title: '', key: 'actions', sortable: false, width: '48px', align: 'center' },
            ],
            loading: false,
            saving: false,
            loadError: null,

            productDialog: false,
            productSearchQuery: '',
            productResults: [],
            productSearchLoading: false,
            productSearchDebounce: null,

            customerSearchSelection: null,
            customerResults: [],
            customerSearchLoading: false,
            customerCreateDialog: false,
            creatingCustomer: false,
            newCustomer: { fname: '', lname: '', email: '', phone: '' },

            addressDialog: false,
            addressesLoading: false,
            customerAddresses: [],
            selectedAddressId: null,
            showNewAddressForm: false,
            newAddress: { fname: '', lname: '', address_line1: '', address_line2: '', city: '', postcode: '', country: 'GB', phone: '' },
            savingNewAddress: false,
            settingAddress: false,

            notesForm: { internal_note: '', customer_note: '' },

            discountPanelOpen: false,
            discountForm: { type: 'percentage', value: 0 },

            deliveryPanelOpen: false,
            deliveryConfirmed: false,
            shipMethods: [],
            shippingForm: { shipping_total: 0, ship_method_id: null },

            recordPaymentDialog: false,
            recordingPayment: false,
            newPayment: { amount: 0, payment_method: 'card', payment_reference: '', notes: '' },

            createOrderDialog: false,
            convertingOrder: false,
            overrideStock: false,
            sendInvoiceOnCreate: true,
            stockShortages: [],

            availableTagNames: [],
            selectedTagNames: [],

            snackbar: { show: false, text: '', color: 'success' },
        };
    },
    computed:{
        dayjs() {
            return dayjs
        },
        items() {
            return this.draftOrder.items || [];
        },
        isConfirmed() {
            return this.draftOrder.status === 'confirmed';
        },
        saveStatusLabel() {
            if (this.saving) return 'Saving…';
            return this.draftOrder.id ? 'Saved' : 'Unsaved draft';
        },
        paymentStatusLabel() {
            const map = { unpaid: 'Unpaid', partially_paid: 'Partially Paid', paid: 'Paid', refunded: 'Refunded' };
            return map[this.draftOrder.payment_status] || 'Unpaid';
        },
        paymentStatusColor() {
            const map = { unpaid: 'grey', partially_paid: 'warning', paid: 'success', refunded: 'error' };
            return map[this.draftOrder.payment_status] || 'grey';
        },
        createProductUrl() {
            return '/products/new';
        },
    },
    created() {
        const id = this.$route.params.id;
        if (id) {
            this.fetchDraftOrder(id);
        }
        this.fetchAvailableTags();
    },
    methods:{
        async fetchDraftOrder(id) {
            this.loading = true;
            this.loadError = null;
            try {
                const { data } = await axios.get(`${BASE_URL}/${id}`);
                this.draftOrder = data.data ?? data;
                this.notesForm.internal_note = this.draftOrder.internal_note;
                this.notesForm.customer_note = this.draftOrder.customer_note;
                this.deliveryConfirmed = Number(this.draftOrder.shipping_total || 0) >= 0 && !!this.draftOrder.shipping_method;
                this.selectedTagNames = (this.draftOrder.tags || []).map(t => t.name);
            } catch (e) {
                this.loadError = 'Could not load this draft order.';
                this.showSnackbar('Failed to load draft order', 'error');
            } finally {
                this.loading = false;
            }
        },
        async ensureDraftExists() {
            if (this.draftOrder.id) return this.draftOrder.id;

            this.saving = true;
            try {
                const { data } = await axios.post(BASE_URL, {});
                this.draftOrder = data.data ?? data;
                return this.draftOrder.id;
            } catch (e) {
                this.showSnackbar('Failed to create draft', 'error');
                throw e;
            } finally {
                this.saving = false;
            }
        },
        async saveDraft() {
            await this.ensureDraftExists();
            this.showSnackbar('Draft saved', 'success');
        },
        goBack() {
            this.$router.back();
        },
        // Products
        openProductDialog() {
            this.productDialog = true;
            this.productSearchQuery = '';
            this.searchProducts('');
        },

        debouncedProductSearch(query) {
            clearTimeout(this.productSearchDebounce);
            this.productSearchDebounce = setTimeout(() => this.searchProducts(query), 350);
        },
        async searchProducts(query) {
            this.productSearchLoading = true;
            try {
                // NOTE: this endpoint (DraftOrderProductSearchController or
                // similar) isn't built on the backend yet — this call will 404
                // until that's added.
                const { data } = await axios.get('/sadmin/products-search', {
                    params: { q: query, per_page: 25 },
                });
                this.productResults = (data.data ?? data).map(p => ({ ...p, selected: false, qty: 1 }));
            } catch (e) {
                this.showSnackbar('Product search failed', 'error');
                this.productResults = [];
            } finally {
                this.productSearchLoading = false;
            }
        },
        async addSelectedProducts() {
            const selected = this.productResults.filter(p => p.selected);
            if (!selected.length) {
                this.productDialog = false;
                return;
            }

            await this.ensureDraftExists();

            for (const p of selected) {
                try {
                    const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/items`, {
                        variant_id: p.variant_id,
                        quantity: p.qty,
                    });
                    if (data.stock_warning) {
                        this.showSnackbar(data.stock_warning, 'warning');
                    }
                    this.draftOrder = data.draft_order.data ?? data.draft_order;
                } catch (e) {
                    this.showSnackbar(`Failed to add ${p.title}`, 'error');
                }
            }

            this.productDialog = false;
            this.showSnackbar(`${selected.length} product(s) added`, 'success');
        },
        changeItemQuantity(item, delta) {
            const newQty = Math.max(1, item.quantity + delta);
            this.updateItemField(item, 'quantity', newQty);
        },

        async updateItemField(item, field, value) {
            try {
                const { data } = await axios.put(`${BASE_URL}/${this.draftOrder.id}/items/${item.id}`, {
                    [field]: value,
                });
                this.draftOrder = data.draft_order.data ?? data.draft_order;
            } catch (e) {
                this.showSnackbar('Failed to update item', 'error');
            }
        },

        async removeItem(item) {
            try {
                const { data } = await axios.delete(`${BASE_URL}/${this.draftOrder.id}/items/${item.id}`);
                this.draftOrder = data.draft_order.data ?? data.draft_order;
            } catch (e) {
                this.showSnackbar('Failed to remove item', 'error');
            }
        },
        // ----------------------------------------------------------------
        // Customer
        // ----------------------------------------------------------------
        async searchCustomers(query) {
            if (!query) return;
            this.customerSearchLoading = true;
            try {
                // NOTE: this endpoint isn't built on the backend yet either —
                // needs a DraftOrderCustomerController@search action.
                const { data } = await axios.get('/sadmin/customers-search', {
                    params: { q: query },
                });
                this.customerResults = (data.data ?? data).map(c => ({ ...c, name: `${c.fname} ${c.lname}` }));
            } catch (e) {
                this.customerResults = [];
            } finally {
                this.customerSearchLoading = false;
            }
        },

        async selectCustomer(customerId) {
            if (!customerId) return;
            await this.ensureDraftExists();
            try {
                const { data } = await axios.put(`${BASE_URL}/${this.draftOrder.id}/customer`, {
                    customer_id: customerId,
                });
                this.draftOrder = data.data ?? data;
                // Per spec: when a customer is attached, either silently use
                // their default address (so the common case needs zero extra
                // clicks) or, if they have none at all, force adding one now
                // rather than letting the draft sit address-less.
                await this.autoAssignDefaultAddress();
            } catch (e) {
                this.showSnackbar('Failed to attach customer', 'error');
                // Same reset as removeCustomer() — on failure the autocomplete
                // stays visible, and without this it'd be stuck on the failed
                // customer_id, blocking a retry the same way.
                this.customerSearchSelection = null;
            }
        },

        async removeCustomer() {
            try {
                const { data } = await axios.delete(`${BASE_URL}/${this.draftOrder.id}/customer`);
                this.draftOrder = data.data ?? data;
                // Without this, customerSearchSelection still holds the removed
                // customer's id — the autocomplete's v-model never resets on its
                // own just because draftOrder.customer became null, which is why
                // reselecting (even the same customer) can silently fail to fire.
                this.customerSearchSelection = null;
                this.customerResults = [];
            } catch (e) {
                this.showSnackbar('Failed to remove customer', 'error');
            }
        },

        // ----------------------------------------------------------------
        // Delivery address
        // ----------------------------------------------------------------

        async openAddressDialog() {
            if (!this.draftOrder.customer) return;
            this.selectedAddressId = this.draftOrder.shipping_address_id || null;
            this.showNewAddressForm = false;
            this.addressDialog = true;
            await this.fetchCustomerAddresses();
        },

        async fetchCustomerAddresses() {
            this.addressesLoading = true;
            try {
                const { data } = await axios.get(`/sadmin/shop-customers/${this.draftOrder.customer.customer_id}/addresses`);
                this.customerAddresses = data.data ?? data;
            } catch (e) {
                this.showSnackbar('Failed to load addresses', 'error');
                this.customerAddresses = [];
            } finally {
                this.addressesLoading = false;
            }
        },

        /**
         * Called right after attaching a customer. Silently uses their
         * default address if they have one (zero extra clicks for the
         * common case) — if they have none at all, opens the dialog with
         * the new-address form already expanded, since a draft can't be
         * converted without one.
         */
        async autoAssignDefaultAddress() {
            await this.fetchCustomerAddresses();

            if (this.customerAddresses.length > 0) {
                const def = this.customerAddresses.find(a => a.is_default) || this.customerAddresses[0];
                await this.applyShippingAddress(def.address_id);
            } else {
                this.selectedAddressId = null;
                this.showNewAddressForm = true;
                this.addressDialog = true;
            }
        },

        async createAndSelectAddress() {
            this.savingNewAddress = true;
            try {
                const { data } = await axios.post(
                    `/sadmin/shop-customers/${this.draftOrder.customer.customer_id}/addresses`,
                    this.newAddress
                );
                const created = data.data ?? data;
                this.customerAddresses.push(created);
                this.selectedAddressId = created.address_id;
                this.showNewAddressForm = false;
                this.newAddress = { fname: '', lname: '', address_line1: '', address_line2: '', city: '', postcode: '', country: 'GB', phone: '' };
                this.showSnackbar('Address saved', 'success');
            } catch (e) {
                this.showSnackbar('Failed to save address', 'error');
            } finally {
                this.savingNewAddress = false;
            }
        },

        async confirmAddressSelection() {
            if (!this.selectedAddressId) return;
            await this.applyShippingAddress(this.selectedAddressId);
            this.addressDialog = false;
        },

        async applyShippingAddress(addressId) {
            this.settingAddress = true;
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/shipping-address`, {
                    address_id: addressId,
                });
                this.draftOrder = data.data ?? data;
                this.showSnackbar('Delivery address set', 'success');
            } catch (e) {
                this.showSnackbar('Failed to set delivery address', 'error');
            } finally {
                this.settingAddress = false;
            }
        },

        async createCustomer() {
            this.creatingCustomer = true;
            try {
                // NOTE: not built on the backend yet — needs a customer creation
                // endpoint that also creates the customer_shops link for the
                // current shop (per your earlier instruction on the create-flow).
                const { data } = await axios.post('/sadmin/shop-customers', this.newCustomer);
                const customer = data.data ?? data;
                await this.selectCustomer(customer.customer_id);
                this.customerCreateDialog = false;
                this.newCustomer = { fname: '', lname: '', email: '', phone: '' };
            } catch (e) {
                this.showSnackbar('Failed to create customer', 'error');
            } finally {
                this.creatingCustomer = false;
            }
        },

        // ----------------------------------------------------------------
        // Discount / Shipping / Notes / Tags
        // ----------------------------------------------------------------
        async applyDiscount() {
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/discount`, {
                    discount_type: this.discountForm.type,
                    discount_value: this.discountForm.value,
                });
                this.draftOrder = data.data ?? data;
                this.discountPanelOpen = false;
            } catch (e) {
                this.showSnackbar('Failed to apply discount', 'error');
            }
        },

        async removeDiscount() {
            try {
                const { data } = await axios.delete(`${BASE_URL}/${this.draftOrder.id}/discount`);
                this.draftOrder = data.data ?? data;
                this.discountForm = { type: 'percentage', value: 0 };
                this.discountPanelOpen = false;
            } catch (e) {
                this.showSnackbar('Failed to remove discount', 'error');
            }
        },

        async openDeliveryPanel() {
            this.deliveryPanelOpen = !this.deliveryPanelOpen;
            if (this.deliveryPanelOpen && this.shipMethods.length === 0) {
                await this.fetchShipMethods();
            }
        },

        async fetchShipMethods() {
            try {
                const { data } = await axios.get('/sadmin/ship-methods');
                this.shipMethods = data.data ?? data;
            } catch (e) {
                // Non-critical — the delivery charge field still works manually
                // even if the method list fails to load.
            }
        },
        onShipMethodSelected(shipMethodId) {
            const method = this.shipMethods.find(m => m.ship_method_id === shipMethodId);
            if (!method) return;
            this.shippingForm.shipping_total = method.price;
            this.shippingForm.method_name = method.method;
            this.shippingForm.courier_name = method.courier_name;
        },

        async confirmShipping() {
            await this.ensureDraftExists();
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/shipping`, {
                    shipping_total: this.shippingForm.shipping_total,
                    shipping_method: this.shippingForm.method_name || "Standard Delivery",
                    courier: this.shippingForm.courier_name,
                });
                this.draftOrder = data.data ?? data;
                this.deliveryConfirmed = true;
                this.deliveryPanelOpen = false;
            } catch (e) {
                this.showSnackbar('Failed to update delivery charge', 'error');
            }
        },

        async saveNotes() {
            if (!this.draftOrder.id) return;
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/notes`, this.notesForm);
                this.draftOrder = data.data ?? data;
            } catch (e) {
                this.showSnackbar('Failed to save notes', 'error');
            }
        },

        async onTagsChanged() {
            await this.ensureDraftExists();
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/tags`, {
                    tag_names: this.selectedTagNames,
                });
                this.draftOrder = data.data ?? data;
                // Re-sync from the server response (which reflects the resolved/
                // deduped/created tags) rather than trusting the raw combobox
                // input as-is — e.g. if the user typed "wholesale" but "Wholesale"
                // already existed, the server's casing wins.
                this.selectedTagNames = (this.draftOrder.tags || []).map(t => t.name);
                await this.fetchAvailableTags();
            } catch (e) {
                this.showSnackbar('Failed to update tags', 'error');
            }
        },

        async fetchAvailableTags() {
            try {
                const { data } = await axios.get('/sadmin/shop-ctags');
                this.availableTagNames = (data.data ?? data).map(t => t.name);
            } catch (e) {
                // Non-critical — the combobox still works for typing new tags,
                // it just won't show existing-tag suggestions.
            }
        },

        // ----------------------------------------------------------------
        // Payments
        // ----------------------------------------------------------------
        async recordPayment() {
            this.recordingPayment = true;
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/payments`, this.newPayment);
                this.draftOrder = data.draft_order.data ?? data.draft_order;
                this.recordPaymentDialog = false;
                this.newPayment = { amount: 0, payment_method: 'card', payment_reference: '', notes: '' };
                this.showSnackbar('Payment recorded', 'success');
            } catch (e) {
                this.showSnackbar('Failed to record payment', 'error');
            } finally {
                this.recordingPayment = false;
            }
        },

        // ----------------------------------------------------------------
        // Convert to order
        // ----------------------------------------------------------------
        openCreateOrderDialog() {
            this.stockShortages = [];
            this.overrideStock = false;
            this.sendInvoiceOnCreate = !!this.draftOrder.customer;
            this.createOrderDialog = true;
        },

        async convertToOrder() {
            this.convertingOrder = true;
            try {
                const { data } = await axios.post(`${BASE_URL}/${this.draftOrder.id}/convert`, {
                    override_stock: this.overrideStock,
                    send_invoice: this.sendInvoiceOnCreate,
                });
                this.draftOrder = data.draft_order.data ?? data.draft_order;
                this.createOrderDialog = false;
                this.showSnackbar(`Order ${data.order_number} created`, 'success');
            } catch (e) {
                if (e.response?.status === 422 && e.response.data?.shortages) {
                    this.stockShortages = e.response.data.shortages;
                } else if (e.response?.status === 422 && e.response.data?.missing_shipping_address) {
                    this.showSnackbar(e.response.data.message || 'A delivery address is required', 'error');
                    this.createOrderDialog = false;
                } else {
                    this.showSnackbar('Failed to create order', 'error');
                    this.createOrderDialog = false;
                }
            } finally {
                this.convertingOrder = false;
            }
        },

        paymentMethodLabel(method) {
            const map = { cash: 'Cash', card: 'Card', bank_transfer: 'Bank transfer', customer_credit: 'Customer credit', other: 'Other' };
            return map[method] || method;
        },

        paymentRecordStatusColor(status) {
            const map = { pending: 'warning', completed: 'success', refunded: 'error' };
            return map[status] || 'grey';
        },

        formatDateTime(value) {
            if (!value) return '—';
            return new Date(value).toLocaleString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        },

        showSnackbar(text, color) {
            this.snackbar = { show: true, text, color };
        },
    }
}
</script>

<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
.card-header-row { padding: 14px 16px; border-bottom: 1px solid #eceef0; display: flex; align-items: center; justify-content: space-between; min-height: 52px; }
.card-header-title { font-size: 14px; font-weight: 600; color: #1a1a1a; }
.muted { color: #6b7177; font-size: 13px; }
.empty-state { padding: 48px 16px; text-align: center; }
.totals-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13.5px; min-height: 30px; }
.totals-row.grand { font-weight: 700; font-size: 15px; border-top: 1px solid #eceef0; padding-top: 10px; margin-top: 4px; }
.sidebar-card { margin-bottom: 16px; }
.adjustment-block { border-top: 1px solid #eceef0; padding: 8px 0; }
.adjustment-block:first-of-type { border-top: none; }
.adjustment-panel { background: #f8f9fa; border: 1px solid #e1e3e6; border-radius: 6px; padding: 12px; margin: 8px 0 4px; }
.product-table thead th { background: #f7f8f9; border-bottom: 1px solid #e1e3e6; text-align: left; font-size: 11.5px; font-weight: 600; color: #5a5f66; padding: 0 14px; text-transform: uppercase; height: 40px; }
.product-table tbody tr { height: 76px; }
.product-table tbody td { border-bottom: 1px solid #eceef0; padding: 0 14px; vertical-align: middle; }
.product-table tbody tr:last-child td { border-bottom: none; }
.product-table tbody tr:hover td { background: #fafbfc; }
.product-cell { display: flex; align-items: center; gap: 12px; }
.product-title { font-size: 13.5px; font-weight: 500; }
.chip-row { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; }
.warn-chip { font-size: 11px !important; height: 20px !important; }
.product-thumb { width: 40px; height: 40px; border-radius: 6px; background: #eceef0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
.cell-field { max-width: 96px; }
.line-total { font-size: 14px; font-weight: 600; }
.qty-stepper { display: flex; align-items: center; justify-content: space-between; border: 1px solid #d5d8db; border-radius: 6px; height: 40px; width: 108px; margin: 0 auto; overflow: hidden; }
.qty-stepper button { width: 32px; height: 100%; border: none; background: #fafbfc; cursor: pointer; }
.qty-stepper input { width: 40px; height: 100%; text-align: center; border: none; outline: none; border-left: 1px solid #e5e7ea; border-right: 1px solid #e5e7ea; }
.row-delete-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: #98a0a8; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
.row-delete-btn:hover { background: #fdecec; color: #c0392b; }
</style>
