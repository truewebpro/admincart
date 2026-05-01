<template>
    <v-dialog v-model="open" max-width="600">
        <v-card>
            <v-card-title class="text-h6 font-weight-medium">Add Customer</v-card-title>
            <v-form v-model="customerForm" @submit.prevent="submitCustomer">
            <v-card-text>
                <v-alert v-if="error" type="error" density="compact" class="mb-2">
                    {{ error }}
                </v-alert>
                <v-row dense>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="cform.fname" label="First Name *" density="compact" variant="outlined"
                                      persistentPlaceholder :rules="fnameRule" />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="cform.lname" label="Last Name *" density="compact" variant="outlined"
                                      persistentPlaceholder :rules="lnameRule" />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="cform.email" label="Email *" density="compact" variant="outlined"
                                      persistentPlaceholder :rules="emailRule" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="cform.phone" label="Phone *" density="compact" variant="outlined"
                                      persistentPlaceholder :rules="phoneRule" />
                    </v-col>
                </v-row>
                <v-divider class="my-2" />
                <div class="text-subtitle-2 mb-2">Address (Optional)</div>
                <v-row dense>
                    <v-col cols="12">
                        <v-text-field v-model="cform.address_line1" label="Address Line 1" density="compact" variant="outlined" persistentPlaceholder />
                    </v-col>
                    <v-col cols="12">
                        <v-text-field v-model="cform.address_line2" label="Address Line 2" density="compact" variant="outlined" persistentPlaceholder />
                    </v-col>
                    <v-col cols="6">
                        <v-text-field v-model="cform.city" label="City" density="compact" variant="outlined" persistentPlaceholder />
                    </v-col>
                    <v-col cols="6">
                        <v-text-field v-model="cform.postcode" label="Postcode" density="compact" variant="outlined" persistentPlaceholder />
                    </v-col>
                    <v-col cols="6">
                        <v-select v-model="cform.country" :items="['United Kingdom']" label="Country" density="compact" variant="outlined" persistentPlaceholder />
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn variant="outlined" @click="close" color="red" density="comfortable">Cancel</v-btn>
                <v-btn variant="elevated" :disabled="!customerForm" :loading="loading" type="submit" color="success" density="comfortable">
                    Save
                </v-btn>
            </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
    <v-dialog v-model="confirmAttach" max-width="400">
        <v-card>
            <v-card-title>Customer Exists</v-card-title>

            <v-card-text>
                This customer already exists in another shop.<br />
                Do you want to add them to this shop?
            </v-card-text>

            <v-card-actions class="justify-end">
                <v-btn @click="confirmAttach = false" variant="outlined">Cancel</v-btn>
                <v-btn color="primary" @click="attachCustomer" variant="elevated" density="comfortable">
                    Yes, Add
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import axios from "axios";

export default {
    name: "AddCustomerDialog",
    props: {
        modelValue: Boolean
    },
    emits: ["update:modelValue", "created"],
    data() {
        return {
            customerForm: false,
            loading: false,
            error: null,
            confirmAttach: false,
            existingCustomerId: null,
            cform: this.getDefaultForm(),
            fnameRule:[
                (v) => !!v || "First Name is required",
                (v) => (v && v.length >= 2) || "Minimum 2 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            lnameRule:[
                (v) => !!v || "Last Name is required",
                (v) => (v && v.length >= 2) || "Minimum 2 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            emailRule:[
                (v) => !!v || "Email is required",
                (v) => (v && v.length <= 60) || "Maximum 80 characters allowed",
                (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || "Email must be valid"
            ],
            phoneRule:[
                (v) => !!v || "Phone is required",
                (v) => /^[0-9]+$/.test(v) || "Only digits allowed",
                (v) => v.length === 10 || "Phone must be exactly 10 digits"
            ],
        };
    },
    computed: {
        open: {
            get() {
                return this.modelValue;
            },
            set(val) {
                this.$emit("update:modelValue", val);
            }
        }
    },
    watch: {
        "cform.phone"(val) {
            const normalized = this.normalizePhone(val);
            if (val !== normalized) {
                this.cform.phone = normalized;
            }
        }
    },
    methods: {
        normalizePhone(value) {
            if (!value) return "";
            let v = value.replace(/\s+/g, ""); // remove spaces
            v = v.replace(/^0+/, "");
            return v;
        },
        getDefaultForm() {
            return {
                fname: "",
                lname: "",
                email: "",
                phone: "",
                address_title: "Default",
                address_line1: "",
                address_line2: "",
                city: "",
                postcode: "",
                country: "United Kingdom",
                is_default:true,
            };
        },
        close() {
            this.open = false;
            this.cform = this.getDefaultForm();
        },
        async submitCustomer() {
            if (!this.cform.email) return;
            this.loading = true;
            try {
                // 1. check if exists
                const check = await axios.post("/sadmin/customer/check-exists", {
                    email:this.cform.email
                });
                const data = check.data;

                // 2. Case: new customer
                if (!data.exists) {
                    await this.createCustomer();
                    return;
                }

                // 3. Case: already in this shop
                if (data.in_current_shop) {
                    this.error = "Customer already exists in this shop";
                    return;
                }

                // 4. Case: exists in another shop
                this.confirmAttach = true;
                this.existingCustomerId = data.customer_id;
                this.open = false;
            } catch (e) {
                console.error(e);
                this.error = "Something went wrong";
            } finally {
                this.loading = false;
            }
        },
        async createCustomer() {
            const resp = await axios.post("/sadmin/customer/add-new", this.cform);
            this.$emit("created", resp.data.customer);
            this.close();
        },
        async attachCustomer() {
            this.loading = true;
            try {
                await axios.post("/sadmin/customer/attach", {
                    customer_id: this.existingCustomerId
                });

                this.$emit("created", { attached: true });
                this.close();
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
                this.confirmAttach = false;
            }
        }
    }
};
</script>
