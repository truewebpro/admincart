<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="6">
                <h2>Integrations</h2>
            </v-col>
            <v-col cols="12" md="6"></v-col>
        </v-row>
        <v-row v-for="(ilist,ldx) in ilists" :key="ldx" class="mb-10 bg-white elevation-3 rounded-shaped">
            <v-col cols="12" md="12" class="pb-0">
                <h2 class="text-decoration-underline text-blue-darken-4"> <v-icon class="me-1">{{ilist.icon}}</v-icon> {{ilist.name}}</h2>
            </v-col>
            <v-col v-for="(partner,pdx) in ilist.partners" :key="pdx"  cols="12" md="4">
                <v-card class="elevation-1 border-0 bg-blue-lighten-5">
                    <v-card-item :title="partner.title" appendIcon="mdi-dots-vertical"></v-card-item>
                    <v-card-text>
                        <v-img :src="partner.logo" contain aspectRatio="16 / 9" max-height="60" min-height="60"/>

                    </v-card-text>
                    <v-card-actions>{{partner.desc}}</v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name: "IntegrateList",
    data(){
        return {
            ilists:[
                {name:'Shipping',icon:"mdi-truck-fast",partners:[
                        {name:'SendCloud',title:"Sendcloud",
                            desc:"Manage All couriers at 1 Place",
                            logo:"https://www.sendcloud.com/wp-content/uploads/2022/09/Sendcloud_small_rgb.svg"},
                        {name:'RoyalMail',title:"Roylmail",
                            desc:"Manage Royalmail OBA Account",
                            logo:"https://www.royalmail.com/themes/custom/rmlcwr/rmg_logo.svg"},
                        {name:'DpdDomestic',title:"DPD",
                            desc:"Manage DPD Domestic Account",
                            logo:"https://www.dpd.com/wp-content/themes/DPD_NoLogin/images/DPD_logo_redgrad_rgb_responsive.svg"},
                    ]},
                {name:'Payments',icon:"mdi-account-credit-card-outline",partners:[
                        {name:'StripeApp',title:"Stripe",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"https://upload.wikimedia.org/wikipedia/commons/thumb/b/ba/Stripe_Logo%2C_revised_2016.svg/1280px-Stripe_Logo%2C_revised_2016.svg.png"},
                        {name:'VivaWallet',title:"Viva Wallet",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"https://vikwp.com/images/plugins/vivawallet.png"},
                        {name:'WorldPay',title:"Worldpay",
                            desc:"Managed Hosting frontend & backend",
                            logo:"https://wp.logos-download.com/wp-content/uploads/2016/11/Worldpay_logo_logotype.png"},
                        {name:'AuthorizeNet',title:"Authorize net",
                            desc:"Managed Pay by all Debit/Credit Cards",
                            logo:"https://upload.wikimedia.org/wikipedia/commons/1/1d/Authorize.Net%2C_A_Visa_Solution_wordmark.svg"},
                        {name:'BoodilPay',title:"Boodil",
                            desc:"Bank Transfer payment in EU Banks",
                            logo:"https://res.cloudinary.com/dm51sphxx/image/upload/v1671023775/boodil/boodil-logo-sig_fbwig1.png"},
                        {name:'BankTransfer',title:"Bank Transfer",
                            desc:"Direct payment in your Bank",
                            logo:"https://www.onlinepoker.net/wp-content/uploads/2018/05/wire-transfer-logo.png"},
                    ]},
                {name:'Email',icon:"mdi-email-fast-outline",partners:[
                        {name:'MailTrap',title:"Mailtrap",
                            desc:"Manage all email campaigns with no Limit",
                            logo:"https://mailtrap.io/wp-content/uploads/2025/11/mailtrap-new-dark-2.svg"},
                        {name:'MailChimp',title:"Mailchimp",
                            desc:"Manage email campaigns with Intuit",
                            logo:"https://1000logos.net/wp-content/uploads/2023/04/Mailchimp-logo.png"},
                        {name:'KlaviyoMain',title:"Klaviyo",
                            desc:"Manage ecommerce campaigns with klaviyo",
                            logo:"https://upload.wikimedia.org/wikipedia/commons/thumb/2/22/Klaviyo-primary-logo-charcoal.svg/3840px-Klaviyo-primary-logo-charcoal.svg.png"},
                        {name:'SendGrid',title:"Sendgrid",
                            desc:"Send bulk email campaigns with sendgrid",
                            logo:"https://upload.wikimedia.org/wikipedia/commons/b/b6/SendGrid_2016_Logo.png"},
                    ]},
                {name:'Hosting',icon:"mdi-web-sync",partners:[
                        {name:'AwsAmplify',title:"AWS Amplify",
                            desc:"Auto deploy frontend with CDN",
                            logo:"https://miro.medium.com/v2/1*EB-8t3dR5qMQYNXyqgM2wQ.png"},
                        {name:'VercelApp',title:"Vercel",
                            desc:"Auto deploy frontend with Analytics",
                            logo:"https://www.drupal.org/files/vercel-logotype-dark.png"},
                        {name:'HostingerCloud',title:"Hostinger",
                            desc:"Managed Hosting frontend & backend",
                            logo:"https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Hostinger_logo_purple.svg/1280px-Hostinger_logo_purple.svg.png"},
                        {name:'DigitalOcean',title:"Digital Ocean",
                            desc:"Managed Cloud Hosting frontend & backend",
                            logo:"https://cdn.worldvectorlogo.com/logos/digitalocean-2.svg"},
                    ]}
            ]
        }
    }
}
</script>

<style scoped>

</style>
