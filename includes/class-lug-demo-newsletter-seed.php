<?php
/** Idempotent sample data for the email-disabled public demo only. */
if (!defined('ABSPATH')) exit;

final class LUG_Demo_Newsletter_Seed {
    const OPTION = 'lug_demo_newsletter_seed';
    const SOURCE = 'lug-demo-newsletter-v1';
    const META = '_lug_demo_newsletter_seed';

    public function hooks() {
        add_action('init',[$this,'maybe_seed'],30);
        add_filter('lug_demo_seed_adapters',[$this,'adapter']);
    }
    public function adapter($adapters) {
        $adapters['newsletter']=['label'=>'Newsletter samples (email disabled)','run'=>[$this,'seed'],'reset'=>[$this,'reset']];
        return $adapters;
    }
    public function maybe_seed() {
        if (!LUG_Demo_Mail_Safety::applies() || !class_exists('LUG_Newsletter_Workspace') || !class_exists('LUG_Newsletter_Store')) return;
        $state=get_option(self::OPTION,[]);
        if (!empty($state['complete'])) return;
        $this->seed();
    }
    public function seed($batch='') {
        if (!LUG_Demo_Mail_Safety::applies()) return new WP_Error('demo_scope','Newsletter samples are restricted to LUG Tools.');
        if (!class_exists('LUG_Newsletter_Workspace') || !class_exists('LUG_Newsletter_Store')) return new WP_Error('demo_newsletter_missing','Install and activate Newsletter before seeding its examples.');
        $state=get_option(self::OPTION,[]);
        if (!empty($state['complete'])) return $state;
        if (!add_option(self::OPTION.'_lock',time(),'',false)) return new WP_Error('demo_seed_busy','Newsletter sample setup is already running.');
        try {
            if ($state) $this->reset();
            $data=require LUG_DEMO_DIR.'fixtures/newsletter-data.php';
            $state=['source'=>self::SOURCE,'posts'=>[],'drafts'=>[],'subscribers'=>[],'report'=>0,'complete'=>false];
            update_option(self::OPTION,$state,false);
            foreach ($data['drafts'] as $draft) {
                foreach ($draft['items'] as &$item) {
                    $item['url']=home_url($item['path']);unset($item['path']);
                    if (isset($item['date_offset'])) {$item['date']=wp_date('Y-m-d',strtotime('+'.(int)$item['date_offset'].' days'));unset($item['date_offset']);}
                }
                unset($item);
                $draft['cutoff']=wp_date('Y-m-d',strtotime('-1 month'));$draft['through']=wp_date('Y-m-d');
                $saved=LUG_Newsletter_Workspace::save($draft);
                if (is_wp_error($saved)) throw new RuntimeException('Could not create a sample draft.');
                $state['posts'][]=$saved['id'];$state['drafts'][]=$saved['id'];
                update_post_meta($saved['id'],self::META,self::SOURCE);update_option(self::OPTION,$state,false);
            }
            global $wpdb;
            $now=gmdate('Y-m-d H:i:s');
            foreach ($data['contacts'] as $contact) {
                // Never change an existing contact, including an existing opt-out.
                if (LUG_Newsletter_Store::subscriber($contact['email'])) continue;
                $row=$contact+['source'=>self::SOURCE,'token'=>bin2hex(random_bytes(32)),'updated_at'=>$now,'status_changed_at'=>$now];
                if ($contact['status']==='subscribed') $row['consent_at']=$now;
                if ($contact['status']==='unsubscribed') $row['unsubscribed_at']=$now;
                if (!$wpdb->insert(LUG_Newsletter_Store::table('subscribers'),$row)) throw new RuntimeException('Could not create a fictional contact.');
                $state['subscribers'][]=(int)$wpdb->insert_id;update_option(self::OPTION,$state,false);
            }
            $run=wp_insert_post(['post_type'=>'lug_nl_run','post_status'=>'private','post_title'=>'Sample report — simulated club update (no email sent)'],true);
            if (is_wp_error($run)) throw new RuntimeException('Could not create the simulated report.');
            $state['posts'][]=$run;$state['report']=$run;update_post_meta($run,self::META,self::SOURCE);update_option(self::OPTION,$state,false);
            update_post_meta($run,'_ln_payload',['settings'=>['title'=>'Simulated club update','subject_line'=>'Sample report — no email sent'],'recipients'=>['list_id'=>'local']]);
            update_post_meta($run,'_ln_run_status','sent');update_post_meta($run,'_ln_run_sent',gmdate('c'));
            $url=home_url('/plugin-tour/');$link=hash('sha256',$url);update_post_meta($run,'_ln_links',[$link=>$url]);
            $index=0;
            foreach ($state['subscribers'] as $subscriber) {
                $row=$wpdb->get_row($wpdb->prepare('SELECT status FROM '.LUG_Newsletter_Store::table('subscribers').' WHERE id=%d',$subscriber),ARRAY_A);
                if (!$row || $row['status']!=='subscribed') continue;
                // Closed synthetic jobs only: nothing pending, claimed or scheduled.
                if (!$wpdb->insert(LUG_Newsletter_Store::table('jobs'),['campaign'=>$run,'subscriber'=>$subscriber,'token'=>bin2hex(random_bytes(32)),'status'=>'accepted','due_at'=>$now,'accepted_at'=>$now])) throw new RuntimeException('Could not create a closed sample report row.');
                $job=(int)$wpdb->insert_id;
                if ($index<2 && !$wpdb->insert(LUG_Newsletter_Store::table('events'),['campaign'=>$run,'job'=>$job,'kind'=>'open','link_id'=>'','created_at'=>$now])) throw new RuntimeException('Could not create a simulated open.');
                if ($index===0 && !$wpdb->insert(LUG_Newsletter_Store::table('events'),['campaign'=>$run,'job'=>$job,'kind'=>'click','link_id'=>$link,'created_at'=>$now])) throw new RuntimeException('Could not create a simulated click.');
                $index++;
            }
            $state['complete']=true;update_option(self::OPTION,$state,false);
            return $state;
        } catch (Throwable $error) {
            return new WP_Error('demo_seed_failed',$error->getMessage());
        } finally { delete_option(self::OPTION.'_lock'); }
    }
    public function reset($batch='') {
        if (!LUG_Demo_Mail_Safety::applies() || !class_exists('LUG_Newsletter_Store')) return new WP_Error('demo_scope','Newsletter sample reset is restricted to LUG Tools.');
        $state=get_option(self::OPTION,[]);global $wpdb;
        foreach ($state['posts']??[] as $post) {
            if (get_post_meta($post,self::META,true)!==self::SOURCE) continue;
            foreach (['events','jobs'] as $table) $wpdb->delete(LUG_Newsletter_Store::table($table),['campaign'=>$post]);
            wp_delete_post($post,true);
        }
        foreach ($state['subscribers']??[] as $id) {
            $row=$wpdb->get_row($wpdb->prepare('SELECT email,source FROM '.LUG_Newsletter_Store::table('subscribers').' WHERE id=%d',$id),ARRAY_A);
            if (!$row || $row['source']!==self::SOURCE || !str_ends_with($row['email'],'@example.org')) continue;
            $wpdb->delete(LUG_Newsletter_Store::table('subscribers'),['id'=>$id,'source'=>self::SOURCE]);
        }
        delete_option(self::OPTION);
        return ['reset'=>true];
    }
}
