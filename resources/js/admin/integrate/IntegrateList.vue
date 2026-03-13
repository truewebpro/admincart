<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="6">
                <h2>Integrations</h2>
            </v-col>
            <v-col cols="12" md="6"></v-col>
        </v-row>
        <v-row v-for="(ilist,ldx) in ilists" :key="ldx" class="mb-16">
            <v-col cols="12" md="12" class="pb-0">
                <h2 class="text-decoration-underline text-grey-darken-3"> <v-icon class="me-1">{{ilist.icon}}</v-icon> {{ilist.name}}</h2>
            </v-col>
            <v-col cols="12" md="12" class="pb-0">
                <p class="text-body-1">{{ilist.desc}}</p>
            </v-col>
            <v-col v-for="(partner,pdx) in ilist.partners" :key="pdx"  cols="12" md="4">
                <v-card class="elevation-3 bg-white rounded-lg">
                    <v-card-text class="d-flex align-center">
                        <div class="me-2 overflow-hidden">
                            <v-img class="rounded-lg" :src="cdn+partner.logo" contain aspectRatio="1" min-width="60" max-width="60" max-height="60" min-height="60"/>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h5">{{partner.title}}</div>
                            <div>{{partner.desc}}</div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
                <!--                <v-card class="elevation-1 border-0 bg-blue-lighten-5">-->
                <!--                    <v-card-item :title="partner.title" appendIcon="mdi-dots-vertical"></v-card-item>-->
                <!--                    <v-card-text>-->
                <!--                        <v-img :src="partner.logo" contain aspectRatio="16 / 9" max-height="60" min-height="60"/>-->

                <!--                    </v-card-text>-->
                <!--                    <v-card-actions>{{partner.desc}}</v-card-actions>-->
                <!--                </v-card>-->
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name: "IntegrateList",
    data(){
        return {
            cdn:this.$store.state.cdn,
            ilists:[
                {name:'Shipping Carriers',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best carriers via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'SendCloud',title:"Sendcloud",
                            desc:"Manage All couriers at 1 Place",
                            logo:"icons/sendcloud.png"},
                        {name:'RoyalMail',title:"Royalmail",
                            desc:"Manage Royalmail OBA Account",
                            logo:"icons/royal-mail.png"},
                        {name:'ClicknDrop',title:"Click n Drop",
                            desc:"Manage Click n Drop Account",
                            logo:"icons/click-and-drop.jpg"},
                        {name:'DpdDomestic',title:"DPD",
                            desc:"Manage DPD Domestic Account",
                            logo:"icons/dpd.webp"},
                        {name:'PacknGo',title:"Pack and Go",
                            desc:"Manage package at one place",
                            logo:"icons/pack-and-go.png"},
                        {name:'IpackyShip',title:"iPacky",
                            desc:"Manage package at one place",
                            logo:"icons/ipacky.png"},
                    ]},
                {name:'Payment Methods',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Payment via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'StripeApp',title:"Stripe",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/stripe.jpeg"},
                        {name:'VivaWallet',title:"Viva Wallet",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/viva-wallet.jpeg"},
                        {name:'CyberSource',title:"Cyber Source",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/cybersource.jpeg"},
                        {name:'KlaranaPay',title:"Klarana",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/klarana.jpeg"},
                        {name:'WorldPay',title:"Worldpay",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/worldpay.jpeg"},
                        {name:'AuthorizeNet',title:"Authorize net",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"icons/authorizenet.png"},
                        {name:'BoodilPay',title:"Boodil",
                            desc:"Bank Transfer payment in EU Banks",
                            logo:"icons/boodil.png"},
                        {name:'BankTransfer',title:"Wire Transfer",
                            desc:"Direct payment in your Bank",
                            logo:"icons/wire-transfer.webp"},
                    ]},
                {name:'Marketing',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Email Marketing via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'MailTrap',title:"Mailtrap",
                            desc:"Manage all email campaigns with no Limit",
                            logo:"icons/mailtrap.jpeg"},
                        {name:'MailChimp',title:"Mailchimp",
                            desc:"Manage email campaigns with Intuit",
                            logo:"icons/mailchimp.jpeg"},
                        {name:'KlaviyoMain',title:"Klaviyo",
                            desc:"Manage ecommerce campaigns with klaviyo",
                            logo:"icons/klaviyo.jpeg"},
                        {name:'SendGrid',title:"Sendgrid",
                            desc:"Send bulk email campaigns with sendgrid",
                            logo:"icons/sendgrid.webp"},
                        {name:'ZohoMail',title:"Zoho",
                            desc:"Send bulk email campaigns with Zoho",
                            logo:"icons/zoho.jpeg"},
                        {name:'ZenDesk',title:"Zendesk",
                            desc:"Send bulk marketing campaigns with Zendesk",
                            logo:"icons/zendesk.png"},
                    ]},
                {name:'Marketplaces',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Marketplaces via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'AmazonStore',title:"Amazon",
                            desc:"Manage Seller Central Account",
                            logo:"icons/amazon.png"},
                        {name:'EbayStore',title:"eBay",
                            desc:"Manage Seller account",
                            logo:"icons/ebay.png"},
                        {name:'EtsyStore',title:"Etsy",
                            desc:"Manage Products Feed",
                            logo:"icons/esty.png"},
                    ]},
                {name:'Socials',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Socials via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'FaceBook',title:"Facebook",
                            desc:"Manage facebook feeds",
                            logo:"icons/facebook.jpeg"},
                        {name:'InstaGram',title:"Instagram",
                            desc:"Share Instagram feeds",
                            logo:"icons/insta.jpeg"},
                        {name:'GoogleFeed',title:"Google",
                            desc:"Manage Google Account",
                            logo:"icons/google.jpeg"},
                        {name:'WhatsApp',title:"Whatsapp",
                            desc:"Manage Whatsapp Sharing",
                            logo:"icons/whatsapp.png"},
                        {name:'TikTok',title:"Tiktok",
                            desc:"Manage Tiktok Sharing",
                            logo:"icons/tiktok.png"},
                        {name:'Pinterest',title:"Pinterest",
                            desc:"Manage Image Sharing",
                            logo:"icons/pineterst.jpeg"},
                        {name:'YouTube',title:"Youtube",
                            desc:"Manage Videos and Channels",
                            logo:"icons/youtube.jpeg"},
                    ]},
                {name:'Inventory Management',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Inventory Manage via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'VeeqoStore',title:"Veeqo",
                            desc:"Inventory sync with Veeqo",
                            logo:"icons/veeqo.jpeg"},
                        {name:'ZohoStore',title:"Zoho",
                            desc:"Inventory sync with Zoho",
                            logo:"icons/zoho.jpeg"},
                    ]},
                {name:'Frameworks',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best Platforms via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'ShopifyStore',title:"Shopify",
                            desc:"Migrate from Shopify",
                            logo:"icons/shopify.png"},
                        {name:'WooCommerce',title:"Woo-Commerce",
                            desc:"Migrate from Wordpress",
                            logo:"icons/woocomerce.jpeg"},
                        {name:'PrestaShop',title:"Prestashop",
                            desc:"Migrate from Prestshop",
                            logo:"icons/prestashop.png"},
                        {name:'OpenCart',title:"Opencart",
                            desc:"Migrate from Opencart",
                            logo:"icons/opencart.png"},
                        {name:'MagentoCart',title:"Magento",
                            desc:"Migrate from Magento",
                            logo:"icons/magento.webp"},
                    ]},
                {name:'Hosting',icon:"mdi-cog",
                    desc:"Seamlessly connect to the best hosting via our pre-negotiated contracts, or upload your own and integrate with our API",
                    partners:[
                        {name:'AwsAmplify',title:"AWS Amplify",
                            desc:"Auto deploy frontend with CDN",
                            logo:"icons/aws.jpeg"},
                        {name:'VercelApp',title:"Vercel",
                            desc:"Auto deploy frontend with Analytics",
                            logo:"icons/vercel.png"},
                        {name:'HostingerCloud',title:"Hostinger",
                            desc:"Managed Hosting frontend & backend",
                            logo:"icons/hostinger.jpeg"},
                        {name:'DigitalOcean',title:"Digital Ocean",
                            desc:"Managed Cloud Hosting frontend & backend",
                            logo:"icons/digital-ocean.png"},
                    ]}
            ]
        }
    }
}
</script>

<style scoped>

</style>
